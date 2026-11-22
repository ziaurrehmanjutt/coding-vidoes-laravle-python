<?php

use App\Enums\VideoStatus;
use App\Models\Project;
use App\Services\ProjectTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
});

it('lists the studio projects and the templates', function () {
    Project::factory()->create(['name' => 'My first studio video']);

    $this->get(route('projects.index'))
        ->assertOk()
        ->assertSee('My first studio video')
        ->assertSee('Type some code')
        ->assertSee('Compare two things');
});

it('creates a project from every template', function (string $template) {
    $this->post(route('projects.store'), ['template' => $template])->assertRedirect();

    $project = Project::sole();

    expect($project->document['objects'])->not->toBeEmpty()
        ->and($project->name)->toBe(ProjectTemplates::options()[$template]['label']);
})->with(array_keys(ProjectTemplates::options()));

it('rejects an unknown template', function () {
    $this->post(route('projects.store'), ['template' => 'nonsense'])->assertSessionHasErrors('template');
});

it('opens the editor with the project and its voices', function () {
    $project = Project::factory()->create(['name' => 'Editor test']);

    $this->get(route('projects.edit', $project))
        ->assertOk()
        ->assertSee('id="studio"', false)
        ->assertSee('Typing code')
        ->assertSee('Make the video (MP4)');
});

it('saves the document from the editor', function () {
    $project = Project::factory()->create();
    $document = ProjectTemplates::document('flow');
    $document['duration'] = 20;

    $this->putJson(route('projects.update', $project), ['name' => 'Renamed', 'document' => $document])
        ->assertOk()
        ->assertJson(['saved' => true]);

    $project->refresh();

    expect($project->name)->toBe('Renamed')
        ->and($project->document['duration'])->toBe(20)
        ->and($project->document['objects'])->toHaveCount(count($document['objects']));
});

it('keeps the keyframes when saving', function () {
    $project = Project::factory()->create();
    $document = ProjectTemplates::document('flow');

    $this->putJson(route('projects.update', $project), ['name' => 'Keys', 'document' => $document])->assertOk();

    $box = collect($project->refresh()->document['objects'])->firstWhere('id', 'box');

    expect($box['tracks']['x'])->toHaveCount(2)
        ->and($box['tracks']['x'][1]['v'])->toBe(660);
});

it('refuses an invalid document', function (array $change, string $field) {
    $project = Project::factory()->create();
    $document = ProjectTemplates::document('code');

    $this->putJson(route('projects.update', $project), ['name' => 'x', 'document' => array_replace_recursive($document, $change)])
        ->assertJsonValidationErrors($field);
})->with([
    'too long' => [['duration' => 500], 'document.duration'],
    'bad colour' => [['background' => 'red'], 'document.background'],
    'unknown object type' => [['objects' => [0 => ['type' => 'virus']]], 'document.objects.0.type'],
    'negative start' => [['objects' => [0 => ['start' => -3]]], 'document.objects.0.start'],
]);

it('refuses a document with too many objects', function () {
    $project = Project::factory()->create();
    $document = ProjectTemplates::document('blank');
    $document['objects'] = array_fill(0, 81, $document['objects'][0]);

    $this->putJson(route('projects.update', $project), ['name' => 'x', 'document' => $document])
        ->assertJsonValidationErrors('document.objects');
});

it('asks Python to render the saved project', function () {
    Http::preventStrayRequests();
    Http::fake(['*/render-project' => Http::response(['accepted' => true], 202)]);

    $project = Project::factory()->create();

    $this->postJson(route('projects.render', $project))
        ->assertStatus(202)
        ->assertJson(['status' => 'pending']);

    Http::assertSent(fn (Request $request) => $request->url() === config('video.python_url').'/render-project'
        && $request['project_id'] === $project->id
        && $request['output_path'] === 'videos/project-'.$project->id.'.mp4'
        && $request['document']['objects'][0]['type'] === 'text'
        && $request['settings']['voice'] === config('video.defaults.voice')
        && $request->hasHeader('X-Api-Token', config('video.python_token')));
});

it('reports a clear error when Python is not running', function () {
    Http::fake(['*/render-project' => Http::response([], 500)]);

    $project = Project::factory()->create();

    $this->postJson(route('projects.render', $project))->assertStatus(503);

    expect($project->refresh()->render_status)->toBe(VideoStatus::Failed);
});

it('does not start a second render while one is running', function () {
    Http::preventStrayRequests();

    $project = Project::factory()->create(['render_status' => VideoStatus::Rendering]);

    $this->postJson(route('projects.render', $project))->assertStatus(409);
});

it('reports the render status and serves the finished file', function () {
    Storage::disk('local')->put('videos/project-1.mp4', 'fake');
    $project = Project::factory()->create([
        'render_status' => VideoStatus::Completed,
        'render_progress' => 100,
        'video_path' => 'videos/project-1.mp4',
    ]);

    $this->getJson(route('projects.status', $project))
        ->assertOk()
        ->assertJson(['status' => 'completed', 'finished' => true, 'playable' => true]);

    $this->get(route('projects.file', $project))->assertOk();
    $this->get(route('projects.download', $project))->assertOk()->assertDownload();
});

it('has no file to serve before the render finishes', function () {
    $project = Project::factory()->create();

    $this->get(route('projects.file', $project))->assertNotFound();
    $this->get(route('projects.download', $project))->assertNotFound();
});

it('deletes a project together with its video', function () {
    Storage::disk('local')->put('videos/project-9.mp4', 'fake');
    $project = Project::factory()->create(['video_path' => 'videos/project-9.mp4']);

    $this->delete(route('projects.destroy', $project))->assertRedirect(route('projects.index'));

    expect(Project::count())->toBe(0);
    Storage::disk('local')->assertMissing('videos/project-9.mp4');
});

it('shows Studio in the main menu', function () {
    $this->get(route('questions.index'))->assertSee('Studio');
});

it('uploads a transparent PNG for a project', function () {
    $project = Project::factory()->create();

    $response = $this->postJson(route('projects.assets.store', $project), [
        'image' => UploadedFile::fake()->image('logo.png', 300, 150),
    ])->assertCreated();

    $response->assertJson(['width' => 300, 'height' => 150]);
    Storage::disk('local')->assertExists('studio/'.$project->id.'/'.$response->json('asset'));

    $this->get(route('projects.assets.show', [$project, $response->json('asset')]))->assertOk();
});

it('refuses uploads that are not pictures', function (UploadedFile $file) {
    $project = Project::factory()->create();

    $this->postJson(route('projects.assets.store', $project), ['image' => $file])->assertJsonValidationErrors('image');
})->with([
    'a script' => fn () => UploadedFile::fake()->createWithContent('run.php', '<?php echo 1;'),
    'an svg' => fn () => UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>'),
    'a huge picture' => fn () => UploadedFile::fake()->image('big.png')->size(9000),
]);

it('does not serve files outside the project pictures', function () {
    $project = Project::factory()->create();

    $this->get('/projects/'.$project->id.'/assets/..%2F..%2F.env')->assertNotFound();
    $this->get(route('projects.assets.show', [$project, 'missing.png']))->assertNotFound();
});

it('keeps a picture object and tells Python where the pictures are', function () {
    Http::preventStrayRequests();
    Http::fake(['*/render-project' => Http::response(['accepted' => true], 202)]);

    $project = Project::factory()->create();
    $document = ProjectTemplates::document('blank');
    $document['objects'][] = ['id' => 'pic', 'type' => 'image', 'asset' => 'abc123.png', 'x' => 10, 'y' => 20, 'w' => 300, 'h' => 300, 'start' => 0, 'duration' => 5];

    $this->putJson(route('projects.update', $project), ['name' => 'Pics', 'document' => $document])->assertOk();
    $this->postJson(route('projects.render', $project))->assertStatus(202);

    Http::assertSent(fn (Request $request) => $request['assets_path'] === 'studio/'.$project->id
        && collect($request['document']['objects'])->contains(fn ($object) => $object['type'] === 'image' && $object['asset'] === 'abc123.png'));
});

it('refuses a picture name that tries to leave the folder', function () {
    $project = Project::factory()->create();
    $document = ProjectTemplates::document('blank');
    $document['objects'][] = ['id' => 'pic', 'type' => 'image', 'asset' => '../../.env', 'x' => 0, 'y' => 0, 'w' => 10, 'h' => 10, 'start' => 0, 'duration' => 1];

    $this->putJson(route('projects.update', $project), ['name' => 'x', 'document' => $document])
        ->assertJsonValidationErrors('document.objects.1.asset');
});

it('removes the uploaded pictures when the project is deleted', function () {
    $project = Project::factory()->create();
    Storage::disk('local')->put('studio/'.$project->id.'/a.png', 'x');

    $this->delete(route('projects.destroy', $project));

    Storage::disk('local')->assertMissing('studio/'.$project->id.'/a.png');
});
