<?php

use App\Enums\ProjectType;
use App\Enums\VideoStatus;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Services\ProjectTemplates;
use Database\Seeders\ProjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
});

it('filters the Studio list by type, category, language, tag and search', function (array $filter, string $shown, string $hidden) {
    Project::factory()->create(['name' => 'Typing demo', 'type' => ProjectType::Code, 'category' => 'Python basics', 'language' => 'python', 'tags' => ['strings', 'beginner']]);
    Project::factory()->create(['name' => 'Versus demo', 'type' => ProjectType::Compare, 'category' => 'JavaScript', 'language' => 'javascript', 'tags' => ['operators']]);

    $this->get(route('projects.index', $filter))
        ->assertOk()
        ->assertSee($shown)
        ->assertDontSee($hidden);
})->with([
    'type' => [['type' => 'code'], 'Typing demo', 'Versus demo'],
    'category' => [['category' => 'JavaScript'], 'Versus demo', 'Typing demo'],
    'language' => [['language' => 'python'], 'Typing demo', 'Versus demo'],
    'tag' => [['tag' => 'operators'], 'Versus demo', 'Typing demo'],
    'search by title' => [['q' => 'Versus'], 'Versus demo', 'Typing demo'],
    'search by tag' => [['q' => 'beginner'], 'Typing demo', 'Versus demo'],
]);

it('filters by whether the video has been made', function () {
    Storage::disk('local')->put('videos/project-1.mp4', 'fake');
    Project::factory()->create(['name' => 'Finished one', 'render_status' => VideoStatus::Completed, 'video_path' => 'videos/project-1.mp4']);
    Project::factory()->create(['name' => 'Waiting one']);
    Project::factory()->create(['name' => 'Busy one', 'render_status' => VideoStatus::Rendering]);

    $this->get(route('projects.index', ['status' => 'made']))->assertSee('Finished one')->assertDontSee('Waiting one')->assertDontSee('Busy one');
    $this->get(route('projects.index', ['status' => 'todo']))->assertSee('Waiting one')->assertDontSee('Finished one');
    $this->get(route('projects.index', ['status' => 'rendering']))->assertSee('Busy one')->assertDontSee('Waiting one');
});

it('rejects unknown filter values', function (array $filter, string $field) {
    $this->get(route('projects.index', $filter))->assertSessionHasErrors($field);
})->with([
    'type' => [['type' => 'nonsense'], 'type'],
    'category' => [['category' => 'Made up'], 'category'],
    'language' => [['language' => 'cobol'], 'language'],
    'status' => [['status' => 'maybe'], 'status'],
]);

it('shows the type counts and the most used tags', function () {
    Project::factory()->count(2)->create(['type' => ProjectType::Tips, 'tags' => ['habits']]);
    Project::factory()->create(['type' => ProjectType::Code, 'tags' => ['habits', 'python']]);

    $this->get(route('projects.index'))
        ->assertSee('Quick tips')
        ->assertSee('#habits')
        ->assertSee('#python');

    expect(Project::tagCounts())->toBe(['habits' => 3, 'python' => 1]);
});

it('saves the type, category, language and tags from the editor', function () {
    $project = Project::factory()->create();

    $this->putJson(route('projects.update', $project), [
        'name' => $project->name,
        'type' => 'tips',
        'category' => 'Git and tools',
        'language' => 'go',
        'tags' => ['  Git ', 'TIPS', 'git', ''],
        'document' => $project->document,
    ])->assertOk();

    $project->refresh();

    expect($project->type)->toBe(ProjectType::Tips)
        ->and($project->category)->toBe('Git and tools')
        ->and($project->language)->toBe('go')
        ->and($project->tags)->toBe(['git', 'tips']);
});

it('rejects an unknown category or type when saving', function () {
    $project = Project::factory()->create();

    $this->putJson(route('projects.update', $project), ['name' => 'x', 'type' => 'bogus', 'category' => 'Nope', 'document' => $project->document])
        ->assertJsonValidationErrors(['type', 'category']);
});

it('starts a new project with the type of its template', function (string $template, ProjectType $type) {
    $this->post(route('projects.store'), ['template' => $template]);

    expect(Project::sole()->type)->toBe($type);
})->with([
    'code' => ['code', ProjectType::Code],
    'flow' => ['flow', ProjectType::Explain],
    'compare' => ['compare', ProjectType::Compare],
    'tip' => ['tip', ProjectType::Tips],
    'blank' => ['blank', ProjectType::Other],
]);

it('copies a project with its pictures but not its video', function () {
    Storage::disk('local')->put('studio/1/logo.png', 'png');
    $project = Project::factory()->create(['name' => 'Original', 'type' => ProjectType::Pictures, 'tags' => ['a'], 'render_status' => VideoStatus::Completed, 'video_path' => 'videos/project-1.mp4']);

    $this->post(route('projects.duplicate', $project))->assertRedirect();

    $copy = Project::query()->where('id', '!=', $project->id)->sole();

    expect($copy->name)->toBe('Original copy')
        ->and($copy->type)->toBe(ProjectType::Pictures)
        ->and($copy->tags)->toBe(['a'])
        ->and($copy->render_status)->toBeNull()
        ->and($copy->video_path)->toBeNull();

    Storage::disk('local')->assertExists($copy->assetsPath().'/logo.png');
    Storage::disk('local')->assertExists($project->assetsPath().'/logo.png');
});

it('seeds the hand-scripted showcase videos and the editor accepts every one', function () {
    $this->seed(ProjectSeeder::class);

    $projects = Project::all();

    expect($projects)->toHaveCount(10)
        ->and($projects->pluck('name')->unique())->toHaveCount(10)
        ->and($projects->pluck('category')->filter()->unique()->count())->toBeGreaterThanOrEqual(7)
        ->and($projects->pluck('type')->unique()->count())->toBeGreaterThanOrEqual(3);

    foreach ($projects as $project) {
        $validator = Validator::make(
            ['name' => $project->name, 'type' => $project->type->value, 'category' => $project->category, 'language' => $project->language, 'tags' => $project->tags, 'document' => $project->document],
            (new UpdateProjectRequest)->rules(),
        );

        expect($validator->errors()->all())->toBe([], $project->name);
    }
});

it('makes the showcase videos long, narrated, with music and real motion', function () {
    $this->seed(ProjectSeeder::class);

    foreach (Project::all() as $project) {
        $objects = collect($project->document['objects']);
        $animated = $objects->flatMap(fn ($object) => array_keys($object['tracks'] ?? []))->unique();

        expect($project->document['duration'])->toBeGreaterThanOrEqual(40, $project->name)
            ->and($project->document['music'])->toBeTrue()
            ->and($objects->where('type', 'voice')->count())->toBeGreaterThanOrEqual(5, $project->name)
            ->and($objects->count())->toBeGreaterThanOrEqual(25, $project->name)
            ->and($animated->count())->toBeGreaterThanOrEqual(1, $project->name);

        // Nothing may start after the video ends, and nothing may run past it.
        foreach ($objects as $object) {
            expect($object['start'] + $object['duration'])->toBeLessThanOrEqual($project->document['duration'] + 0.5, $project->name.': '.$object['name']);
        }
    }

    $everyProp = Project::all()->flatMap(fn ($project) => collect($project->document['objects'])->flatMap(fn ($object) => array_keys($object['tracks'] ?? [])))->unique();

    expect($everyProp->all())->toContain('x', 'y', 'rotation', 'scale', 'opacity');
});

it('gives every showcase video keyframes that fall inside its object', function () {
    $this->seed(ProjectSeeder::class);

    foreach (Project::all() as $project) {
        foreach ($project->document['objects'] as $object) {
            foreach ($object['tracks'] ?? [] as $prop => $keys) {
                foreach ($keys as $key) {
                    expect($key['t'])->toBeLessThanOrEqual($object['duration'] + 0.01, "{$project->name}: {$object['name']} {$prop}");
                }
            }
        }
    }
});

it('does not overwrite seeded projects that were edited', function () {
    $this->seed(ProjectSeeder::class);
    $project = Project::where('name', 'Big O: the algorithm race')->firstOrFail();
    $project->update(['tags' => ['mine']]);

    $this->seed(ProjectSeeder::class);

    expect($project->refresh()->tags)->toBe(['mine'])
        ->and(Project::where('name', 'Big O: the algorithm race')->count())->toBe(1)
        ->and(Project::count())->toBe(10);
});

it('keeps every field of the document when the editor saves, not only the validated ones', function () {
    $project = Project::factory()->create();
    $document = ProjectTemplates::document('code');

    $this->putJson(route('projects.update', $project), ['name' => 'Full save', 'document' => $document])->assertOk();

    $saved = collect($project->refresh()->document['objects'])->keyBy('id');

    expect($saved['code']['x'])->toBe(70)
        ->and($saved['code']['y'])->toBe(520)
        ->and($saved['code']['w'])->toBe(940)
        ->and($saved['code']['size'])->toBe(42)
        ->and($saved['code']['language'])->toBe('python')
        ->and($saved['title']['align'])->toBe('center')
        ->and($saved['title']['size'])->toBe(84)
        ->and($project->document)->toEqual($document);
});

it('saves the upload title, description and hashtags with the project', function () {
    $project = Project::factory()->create();

    $this->putJson(route('projects.update', $project), [
        'name' => $project->name,
        'upload_title' => 'My upload title',
        'upload_description' => "Line one\nLine two",
        'hashtags' => ['#Python', ' coding ', 'coding', '#', '#learn to code', ''],
        'document' => $project->document,
    ])->assertOk();

    $project->refresh();

    expect($project->upload_title)->toBe('My upload title')
        ->and($project->upload_description)->toBe("Line one\nLine two")
        ->and($project->hashtags)->toBe(['Python', 'coding', 'learntocode'])
        ->and($project->hashtagText())->toBe('#Python #coding #learntocode')
        ->and($project->hasUploadDetails())->toBeTrue();
});

it('says the upload details are missing until all three are filled in', function () {
    $project = Project::factory()->create(['upload_title' => 'Title only']);

    expect($project->hasUploadDetails())->toBeFalse();

    $project->update(['upload_description' => 'Description', 'hashtags' => ['a']]);

    expect($project->hasUploadDetails())->toBeTrue();
});

it('refuses an upload title that is too long or too many hashtags', function (array $change, string $field) {
    $project = Project::factory()->create();

    $this->putJson(route('projects.update', $project), array_merge(['name' => 'x', 'document' => $project->document], $change))
        ->assertJsonValidationErrors($field);
})->with([
    'long title' => [['upload_title' => str_repeat('a', 101)], 'upload_title'],
    'long description' => [['upload_description' => str_repeat('a', 5001)], 'upload_description'],
    'too many hashtags' => [['hashtags' => array_map(fn ($i) => "tag$i", range(1, 16))], 'hashtags'],
    'long hashtag' => [['hashtags' => [str_repeat('a', 41)]], 'hashtags.0'],
]);

it('suggests upload details from the video without saving them', function () {
    $document = ProjectTemplates::document('code');
    $document['objects'][] = ['id' => 'v1', 'type' => 'voice', 'name' => 'Voice', 'start' => 0, 'duration' => 3, 'text' => 'Can you guess the output of this loop?'];
    $project = Project::factory()->create([
        'name' => 'Loop quiz', 'category' => 'Python basics', 'language' => 'python', 'tags' => ['loops', 'Beginner Tips'], 'document' => $document,
    ]);

    $response = $this->postJson(route('projects.upload-details', $project))->assertOk();

    expect($response->json('upload_title'))->toBe('Loop quiz')
        ->and($response->json('upload_description'))->toContain('Can you guess the output of this loop?')->toContain('Python basics')
        ->and($response->json('hashtags'))->toContain('loops', 'beginnertips', 'python', 'shorts', 'pythonbasics')
        ->and($response->json('hashtags'))->not->toContain('');

    expect($project->refresh()->upload_title)->toBeNull();
});

it('never suggests a title longer than YouTube allows', function () {
    $project = Project::factory()->create(['name' => str_repeat('Long name ', 20)]);

    expect(mb_strlen($this->postJson(route('projects.upload-details', $project))->json('upload_title')))->toBeLessThanOrEqual(100);
});

it('flags on the Studio list which videos have upload details', function () {
    Project::factory()->create(['name' => 'Ready video', 'upload_title' => 'T', 'upload_description' => 'D', 'hashtags' => ['a']]);
    Project::factory()->create(['name' => 'Plain video']);

    $this->get(route('projects.index'))
        ->assertSee('&#10003; Upload details', false)
        ->assertSee('No upload details');
});

it('shows the upload details panel in the editor with the saved values', function () {
    $project = Project::factory()->create(['upload_title' => 'Saved title', 'upload_description' => 'Saved description', 'hashtags' => ['python', 'coding']]);

    $this->get(route('projects.edit', $project))
        ->assertOk()
        ->assertSee('Upload details')
        ->assertSee('Saved title')
        ->assertSee('Saved description')
        ->assertSee('#python #coding')
        ->assertSee('Suggest from this video');
});

it('gives every showcase video an upload title, description and hashtags', function () {
    $this->seed(ProjectSeeder::class);

    foreach (Project::all() as $project) {
        expect($project->hasUploadDetails())->toBeTrue($project->name)
            ->and(mb_strlen($project->upload_title))->toBeLessThanOrEqual(100)
            ->and(count($project->hashtags))->toBeLessThanOrEqual(15)
            ->and($project->hashtags)->toContain('shorts');
    }
});

it('adds upload details to old showcase videos without touching ones you edited', function () {
    $this->seed(ProjectSeeder::class);

    $old = Project::where('name', 'Big O: the algorithm race')->firstOrFail();
    $old->update(['upload_title' => null, 'upload_description' => null, 'hashtags' => null]);
    $mine = Project::where('name', 'Spot the bug in 8 seconds')->firstOrFail();
    $mine->update(['upload_title' => 'My own title']);

    $this->seed(ProjectSeeder::class);

    expect($old->refresh()->hasUploadDetails())->toBeTrue()
        ->and($mine->refresh()->upload_title)->toBe('My own title');
});
