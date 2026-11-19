<?php

namespace App\Services;

use App\Models\GeneratedVideo;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Talks to the FastAPI service in python_services/.
 */
class PythonVideoService
{
    /**
     * Run the code in Python and return what it prints.
     *
     * @return array{ok: bool, supported: bool, output: string, error: string}
     */
    public function predictOutput(string $code, string $language = 'python'): array
    {
        $response = $this->client()
            ->timeout(15)
            ->post('/predict', ['code' => $code, 'language' => $language]);

        if ($response->failed()) {
            throw new RuntimeException('The Python service rejected the request.');
        }

        return $response->json();
    }

    /**
     * Ask Python to render the video. Python answers right away and keeps
     * updating the generated_videos row while it works.
     */
    public function startRender(GeneratedVideo $video): void
    {
        $question = $video->question;

        $response = $this->client()
            ->timeout(10)
            ->post('/render', [
                'video_id' => $video->id,
                'output_path' => $video->expectedPath(),
                'question' => [
                    'title' => $question->title,
                    'language' => $question->language,
                    'hook' => $question->hook,
                    'series_label' => $question->seriesLabel(),
                    'next_series_label' => $question->nextSeriesLabel(),
                    'question_text' => $question->question_text,
                    'code' => $question->code,
                    'options' => array_values($question->options),
                    'correct_option' => $question->correct_option,
                    'explanation' => $question->explanation,
                ],
                'settings' => $video->settings_snapshot,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('The Python service could not start the render.');
        }
    }

    /**
     * Speak a short sample with the given voice settings and return the MP3 bytes.
     *
     * @param  array<string, mixed>  $settings
     */
    public function previewVoice(array $settings): string
    {
        $response = $this->client()
            ->timeout(60)
            ->post('/tts-preview', [
                'voice' => $settings['voice'],
                'speech_rate' => $settings['speech_rate'],
                'speech_pitch' => $settings['speech_pitch'],
                'speech_volume' => $settings['speech_volume'],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('The voice preview is not available right now.');
        }

        return $response->body();
    }

    public function isReachable(): bool
    {
        try {
            return $this->client()->timeout(3)->get('/health')->successful();
        } catch (ConnectionException) {
            return false;
        }
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(config('video.python_url'))
            ->connectTimeout(3)
            ->withHeaders(['X-Api-Token' => config('video.python_token')])
            ->acceptJson();
    }
}
