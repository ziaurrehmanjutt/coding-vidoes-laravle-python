<?php

use App\Enums\VideoStatus;
use App\Models\GeneratedVideo;
use App\Models\Question;
use App\Models\VideoSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
});

it('creates a pending video and asks Python to render it', function () {
    Http::preventStrayRequests();
    Http::fake(['*/render' => Http::response(['accepted' => true], 202)]);

    VideoSetting::factory()->create(['theme' => 'ocean', 'voice' => 'en-GB-RyanNeural']);
    $question = Question::factory()->create();

    $response = $this->post(route('questions.videos.store', $question));

    $video = GeneratedVideo::sole();
    $response->assertRedirect(route('videos.show', $video));

    expect($video->status)->toBe(VideoStatus::Pending)
        ->and($video->settings_snapshot)->toMatchArray(['theme' => 'ocean', 'voice' => 'en-GB-RyanNeural']);

    Http::assertSent(fn (Request $request) => $request->url() === config('video.python_url').'/render'
        && $request['video_id'] === $video->id
        && $request['output_path'] === 'videos/video-'.$video->id.'.mp4'
        && $request['question']['correct_option'] === 1
        && $request['question']['options'] === ['ython', 'ythoon', 'Pythoon', 'ythonon']
        && $request['settings']['theme'] === 'ocean'
        && $request->hasHeader('X-Api-Token', config('video.python_token')));
});

it('uses the question think time instead of the default when it is set', function () {
    Http::fake(['*/render' => Http::response(['accepted' => true], 202)]);

    $question = Question::factory()->create(['think_seconds' => 12]);

    $this->post(route('questions.videos.store', $question));

    expect(GeneratedVideo::sole()->settings_snapshot['think_seconds'])->toBe(12);
});

it('marks the video as failed when the Python service is not running', function () {
    Http::fake(['*' => fn () => throw new ConnectionException('Connection refused')]);

    $question = Question::factory()->create();

    $this->post(route('questions.videos.store', $question))->assertRedirect();

    $video = GeneratedVideo::sole();

    expect($video->status)->toBe(VideoStatus::Failed)
        ->and($video->message)->toContain('Python service is not running');
});

it('marks the video as failed when Python refuses the render', function () {
    Http::fake(['*/render' => Http::response(['detail' => 'Invalid API token'], 401)]);

    $question = Question::factory()->create();

    $this->post(route('questions.videos.store', $question));

    expect(GeneratedVideo::sole())
        ->status->toBe(VideoStatus::Failed)
        ->message->toBe('The Python service could not start the render.');
});

it('reports the render progress as JSON', function () {
    $video = GeneratedVideo::factory()->create([
        'status' => VideoStatus::Rendering,
        'progress' => 45,
        'message' => 'Drawing and encoding video',
    ]);

    $this->getJson(route('videos.status', $video))
        ->assertOk()
        ->assertExactJson([
            'status' => 'rendering',
            'label' => 'Rendering',
            'progress' => 45,
            'message' => 'Drawing and encoding video',
            'finished' => false,
        ]);
});

it('shows progress while rendering and the player once finished', function () {
    $rendering = GeneratedVideo::factory()->create(['status' => VideoStatus::Rendering, 'progress' => 30]);

    $this->get(route('videos.show', $rendering))
        ->assertOk()
        ->assertSee('data-video-progress', false)
        ->assertDontSee('Download MP4');

    Storage::disk('local')->put('videos/ready.mp4', 'fake');
    $ready = GeneratedVideo::factory()->completed()->create(['path' => 'videos/ready.mp4']);

    $this->get(route('videos.show', $ready))
        ->assertOk()
        ->assertSee('Download MP4')
        ->assertSee(route('videos.file', $ready), false)
        ->assertDontSee('data-video-progress', false);
});

it('shows the failure message and a retry button', function () {
    $video = GeneratedVideo::factory()->failed()->create();

    $this->get(route('videos.show', $video))
        ->assertOk()
        ->assertSee('Renderer crashed')
        ->assertSee('Try again');
});

it('streams and downloads a finished video', function () {
    Storage::disk('local')->put('videos/ready.mp4', 'fake-video');
    $video = GeneratedVideo::factory()->completed()->create(['path' => 'videos/ready.mp4']);

    $this->get(route('videos.file', $video))
        ->assertOk()
        ->assertHeader('Content-Type', 'video/mp4');

    $this->get(route('videos.download', $video))
        ->assertOk()
        ->assertDownload();
});

it('does not serve a video that is not finished or whose file is missing', function () {
    $rendering = GeneratedVideo::factory()->create(['status' => VideoStatus::Rendering]);
    $missing = GeneratedVideo::factory()->completed()->create(['path' => 'videos/gone.mp4']);

    $this->get(route('videos.file', $rendering))->assertNotFound();
    $this->get(route('videos.download', $rendering))->assertNotFound();
    $this->get(route('videos.file', $missing))->assertNotFound();
});

it('lists generated videos', function () {
    $video = GeneratedVideo::factory()->completed()->create();

    $this->get(route('videos.index'))
        ->assertOk()
        ->assertSee($video->question->title);
});

it('deletes a video and its file', function () {
    Storage::disk('local')->put('videos/ready.mp4', 'fake');
    $video = GeneratedVideo::factory()->completed()->create(['path' => 'videos/ready.mp4']);

    $this->delete(route('videos.destroy', $video))->assertRedirect(route('videos.index'));

    expect(GeneratedVideo::count())->toBe(0);
    Storage::disk('local')->assertMissing('videos/ready.mp4');
});
