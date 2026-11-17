<?php

use App\Models\GeneratedVideo;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
});

it('lists questions that are not uploaded yet in posting order', function () {
    Question::factory()->create(['title' => 'Plain one']);
    Question::factory()->create(['title' => 'Challenge one', 'series' => 'challenge', 'series_number' => 1]);
    Question::factory()->create(['title' => 'Day two', 'series' => 'day', 'series_number' => 2]);
    Question::factory()->create(['title' => 'Day one', 'series' => 'day', 'series_number' => 1]);
    Question::factory()->create(['title' => 'Already posted', 'series' => 'day', 'series_number' => 3, 'uploaded_at' => now()]);

    $this->get(route('queue.index'))
        ->assertOk()
        ->assertSeeInOrder(['Day one', 'Day two', 'Challenge one', 'Plain one'])
        ->assertDontSee('Already posted');
});

it('filters the queue by series', function () {
    Question::factory()->create(['title' => 'Day one', 'series' => 'day', 'series_number' => 1]);
    Question::factory()->create(['title' => 'Challenge one', 'series' => 'challenge', 'series_number' => 1]);

    $this->get(route('queue.index', ['series' => 'challenge']))
        ->assertSee('Challenge one')
        ->assertDontSee('Day one');

    $this->get(route('queue.index', ['series' => 'week']))->assertSessionHasErrors('series');
});

it('shows the upload details with copy buttons', function () {
    Question::factory()->create([
        'upload_title' => 'Ready made title',
        'upload_description' => 'Ready made description',
        'hashtags' => ['python', 'quiz'],
    ]);

    $this->get(route('queue.index'))
        ->assertSee('Ready made title')
        ->assertSee('Ready made description')
        ->assertSee('#python #quiz')
        ->assertSee('data-copy-target', false);
});

it('offers the download once the video is ready and a create button before that', function () {
    Storage::disk('local')->put('videos/ready.mp4', 'fake');

    $ready = Question::factory()->create(['title' => 'Has video', 'series' => 'day', 'series_number' => 1]);
    GeneratedVideo::factory()->completed()->for($ready)->create(['path' => 'videos/ready.mp4']);
    $waiting = Question::factory()->create(['title' => 'No video', 'series' => 'day', 'series_number' => 2]);

    $response = $this->get(route('queue.index'))
        ->assertOk()
        ->assertSee('Download MP4')
        ->assertSee('Video ready')
        ->assertSee('No video yet')
        ->assertSee(route('questions.videos.store', $waiting), false);

    $response->assertSee('Next to upload:')->assertSee('Day 1');
});

it('drops a question from the queue once it is marked as uploaded', function () {
    $question = Question::factory()->create(['title' => 'Soon posted']);

    $this->get(route('queue.index'))->assertSee('Soon posted');

    $this->patch(route('questions.uploaded', $question));

    $this->get(route('queue.index'))
        ->assertDontSee('Soon posted')
        ->assertSee('Nothing waiting to be uploaded.');
});
