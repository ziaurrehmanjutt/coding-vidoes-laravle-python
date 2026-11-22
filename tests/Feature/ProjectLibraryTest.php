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

    expect($projects)->toHaveCount(5)
        ->and($projects->pluck('name')->unique())->toHaveCount(5)
        ->and($projects->pluck('category')->filter()->unique()->count())->toBeGreaterThanOrEqual(4);

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
        ->and(Project::count())->toBe(5);
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
