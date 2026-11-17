<?php

use App\Models\VideoSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

/**
 * @return array<string, mixed>
 */
function validSettingData(array $overrides = []): array
{
    return array_merge([
        'voice' => 'en-GB-RyanNeural',
        'speech_rate' => 10,
        'speech_pitch' => -5,
        'speech_volume' => 0,
        'narrate_question' => '1',
        'narrate_explanation' => '1',
        'tick_sound' => '1',
        'think_seconds' => 10,
        'theme' => 'sunset',
        'quality' => 'max',
        'channel_name' => 'My Channel',
    ], $overrides);
}

it('shows the default settings and creates the row on first visit', function () {
    $this->get(route('settings.edit'))
        ->assertOk()
        ->assertSee('Video settings')
        ->assertSee('Preview voice')
        ->assertSee('Video quality');

    expect(VideoSetting::count())->toBe(1)
        ->and(VideoSetting::first()->voice)->toBe(config('video.defaults.voice'))
        ->and(VideoSetting::first()->quality)->toBe('high');
});

it('saves the settings', function () {
    $this->put(route('settings.update'), validSettingData())
        ->assertRedirect(route('settings.edit'));

    expect(VideoSetting::sole())
        ->voice->toBe('en-GB-RyanNeural')
        ->speech_rate->toBe(10)
        ->think_seconds->toBe(10)
        ->theme->toBe('sunset')
        ->quality->toBe('max')
        ->channel_name->toBe('My Channel');
});

it('treats unticked checkboxes as off', function () {
    $this->put(route('settings.update'), validSettingData([
        'narrate_question' => null,
        'narrate_explanation' => null,
        'tick_sound' => null,
    ]));

    expect(VideoSetting::sole())
        ->narrate_question->toBeFalse()
        ->narrate_explanation->toBeFalse()
        ->tick_sound->toBeFalse();
});

it('rejects invalid settings', function (array $overrides, string $errorKey) {
    $this->put(route('settings.update'), validSettingData($overrides))
        ->assertSessionHasErrors($errorKey);
})->with([
    'unknown voice' => [['voice' => 'made-up-voice'], 'voice'],
    'unknown theme' => [['theme' => 'neon'], 'theme'],
    'unknown quality' => [['quality' => '8k'], 'quality'],
    'think time too long' => [['think_seconds' => 99], 'think_seconds'],
    'speech rate too fast' => [['speech_rate' => 500], 'speech_rate'],
    'channel name too long' => [['channel_name' => str_repeat('a', 41)], 'channel_name'],
]);

it('plays a voice preview with the current form values', function () {
    Http::preventStrayRequests();
    Http::fake(['*/tts-preview' => Http::response('mp3-bytes', 200, ['Content-Type' => 'audio/mpeg'])]);

    $this->post(route('settings.preview'), validSettingData())
        ->assertOk()
        ->assertHeader('Content-Type', 'audio/mpeg');

    Http::assertSent(fn ($request) => $request['voice'] === 'en-GB-RyanNeural'
        && $request['speech_rate'] === 10
        && $request['speech_pitch'] === -5);
});

it('reports when the voice preview cannot reach Python', function () {
    Http::fake(['*' => fn () => throw new ConnectionException('Connection refused')]);

    $this->postJson(route('settings.preview'), validSettingData())
        ->assertStatus(503);
});

it('reports when the voice service is unavailable', function () {
    Http::fake(['*/tts-preview' => Http::response(['detail' => 'offline'], 503)]);

    $this->postJson(route('settings.preview'), validSettingData())
        ->assertStatus(502)
        ->assertJson(['message' => 'The voice preview is not available right now.']);
});
