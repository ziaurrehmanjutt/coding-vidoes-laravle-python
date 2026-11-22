<?php

use App\Enums\QuestionStage;
use App\Models\GeneratedVideo;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
});

/**
 * One question for every stage of the workflow.
 *
 * @return array<string, Question>
 */
function questionsInEveryStage(): array
{
    Storage::disk('local')->put('videos/ready.mp4', 'fake');

    $ready = Question::factory()->create(['title' => 'Ready one']);
    GeneratedVideo::factory()->completed()->for($ready)->create(['path' => 'videos/ready.mp4']);

    $uploaded = Question::factory()->create(['title' => 'Uploaded one', 'uploaded_at' => now()]);
    GeneratedVideo::factory()->completed()->for($uploaded)->create(['path' => 'videos/ready.mp4']);

    $rendering = Question::factory()->create(['title' => 'Rendering one']);
    GeneratedVideo::factory()->for($rendering)->create(['status' => 'rendering', 'progress' => 40]);

    $failed = Question::factory()->create(['title' => 'Failed one']);
    GeneratedVideo::factory()->for($failed)->create(['status' => 'failed']);

    return [
        'draft' => Question::factory()->create(['title' => 'Draft one']),
        'ready' => $ready,
        'uploaded' => $uploaded,
        'rendering' => $rendering,
        'failed' => $failed,
    ];
}

it('works out the stage of every question', function () {
    foreach (questionsInEveryStage() as $expected => $question) {
        $question->load(['completedVideo', 'latestVideo']);

        expect($question->stage()->value)->toBe($expected);
    }
});

it('filters the list by stage', function (string $stage, string $title) {
    questionsInEveryStage();

    $response = $this->get(route('questions.index', ['stage' => $stage]))->assertOk();

    $response->assertSee($title);

    foreach (['Draft one', 'Ready one', 'Uploaded one', 'Rendering one', 'Failed one'] as $other) {
        if ($other !== $title) {
            $response->assertDontSee($other);
        }
    }
})->with([
    'to make' => ['draft', 'Draft one'],
    'rendering' => ['rendering', 'Rendering one'],
    'failed' => ['failed', 'Failed one'],
    'ready' => ['ready', 'Ready one'],
    'uploaded' => ['uploaded', 'Uploaded one'],
]);

it('rejects an unknown stage', function () {
    $this->get(route('questions.index', ['stage' => 'sleeping']))->assertSessionHasErrors('stage');
});

it('shows a count on every stage tab', function () {
    questionsInEveryStage();

    expect(Question::stageCounts())->toBe([
        'all' => 5, 'draft' => 1, 'rendering' => 1, 'failed' => 1, 'ready' => 1, 'uploaded' => 1,
    ]);

    $this->get(route('questions.index'))
        ->assertSee('Ready to upload')
        ->assertSee('To make');
});

it('shows the video and upload flags and the matching buttons', function () {
    questionsInEveryStage();

    $this->get(route('questions.index'))
        ->assertSee('Video ready')
        ->assertSee('No video yet')
        ->assertSee('Render failed')
        ->assertSee('Rendering 40%')
        ->assertSee('Uploaded '.now()->format('M j'))
        ->assertSee('Download MP4')
        ->assertSee('Regenerate')
        ->assertSee('Create video')
        ->assertSee('Try again')
        ->assertSee('View progress');
});

it('shows how many videos wait to be uploaded in the menu', function () {
    questionsInEveryStage();

    $this->get(route('questions.index'))->assertSeeInOrder(['Upload queue', '1']);
});

it('creates videos for the ticked questions', function () {
    Http::preventStrayRequests();
    Http::fake(['*/health' => Http::response(['status' => 'ok']), '*/render' => Http::response(['accepted' => true], 202)]);

    $questions = Question::factory()->count(2)->create();
    Question::factory()->create();

    $this->post(route('questions.bulk'), ['action' => 'create_videos', 'ids' => $questions->modelKeys()])
        ->assertRedirect()
        ->assertSessionHas('status');

    expect(GeneratedVideo::count())->toBe(2);
});

it('does not create videos when the Python service is not running', function () {
    Http::fake(['*/health' => Http::response([], 500)]);

    $question = Question::factory()->create();

    $this->post(route('questions.bulk'), ['action' => 'create_videos', 'ids' => [$question->id]])
        ->assertSessionHas('error');

    expect(GeneratedVideo::count())->toBe(0);
});

it('marks several questions as uploaded and back', function () {
    $questions = Question::factory()->count(2)->create();
    $ids = $questions->modelKeys();

    $this->post(route('questions.bulk'), ['action' => 'mark_uploaded', 'ids' => $ids]);

    expect(Question::whereNotNull('uploaded_at')->count())->toBe(2);

    $this->post(route('questions.bulk'), ['action' => 'mark_not_uploaded', 'ids' => $ids]);

    expect(Question::whereNotNull('uploaded_at')->count())->toBe(0);
});

it('keeps the first upload date when a question is marked uploaded twice', function () {
    $question = Question::factory()->create(['uploaded_at' => now()->subDays(5)]);
    $original = $question->uploaded_at->toDateTimeString();

    $this->post(route('questions.bulk'), ['action' => 'mark_uploaded', 'ids' => [$question->id]]);

    expect($question->refresh()->uploaded_at->toDateTimeString())->toBe($original);
});

it('asks for at least one ticked question', function () {
    $this->post(route('questions.bulk'), ['action' => 'create_videos'])->assertSessionHasErrors('ids');
    $this->post(route('questions.bulk'), ['action' => 'delete_everything', 'ids' => [1]])->assertSessionHasErrors('action');
});

it('makes every missing video in one go', function () {
    Http::preventStrayRequests();
    Http::fake(['*/health' => Http::response(['status' => 'ok']), '*/render' => Http::response(['accepted' => true], 202)]);

    $parts = questionsInEveryStage();

    $this->post(route('questions.missing-videos'))->assertSessionHas('status');

    // Only the "draft" and "failed" questions get a new video.
    expect(GeneratedVideo::count())->toBe(6)
        ->and($parts['draft']->videos()->count())->toBe(1)
        ->and($parts['failed']->videos()->count())->toBe(2)
        ->and($parts['ready']->videos()->count())->toBe(1);
});

it('says so when there is nothing missing', function () {
    $this->post(route('questions.missing-videos'))->assertSessionHas('status', 'Every question already has a video.');
});

it('filters the videos page by status and offers regenerate', function () {
    Storage::disk('local')->put('videos/a.mp4', 'fake');

    $question = Question::factory()->create(['title' => 'Has both', 'uploaded_at' => now()]);
    GeneratedVideo::factory()->completed()->for($question)->create(['path' => 'videos/a.mp4']);
    GeneratedVideo::factory()->for(Question::factory()->create(['title' => 'Broken one']))->create(['status' => 'failed']);

    $this->get(route('videos.index', ['status' => 'failed']))
        ->assertSee('Broken one')
        ->assertDontSee('Has both');

    $this->get(route('videos.index'))
        ->assertSee('Download')
        ->assertSee('Regenerate')
        ->assertSee('Uploaded');
});

it('has stages with a label and an edge colour each', function () {
    foreach (QuestionStage::cases() as $stage) {
        expect($stage->label())->not->toBeEmpty()
            ->and($stage->edgeClasses())->toContain('border-l');
    }
});
