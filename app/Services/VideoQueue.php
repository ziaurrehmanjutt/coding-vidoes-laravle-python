<?php

namespace App\Services;

use App\Enums\VideoStatus;
use App\Models\GeneratedVideo;
use App\Models\Question;
use App\Models\VideoSetting;
use RuntimeException;
use Throwable;

/**
 * Creates video records and hands them to the Python service to render.
 */
class VideoQueue
{
    public function __construct(private PythonVideoService $python) {}

    /**
     * Save a pending video with a copy of the current settings, then ask Python to render it.
     * A failure is stored on the video instead of being thrown, so the user can see why.
     */
    public function start(Question $question): GeneratedVideo
    {
        $settings = VideoSetting::current()->only(array_keys(config('video.defaults')));

        if ($question->think_seconds !== null) {
            $settings['think_seconds'] = $question->think_seconds;
        }

        $video = $question->videos()->create([
            'status' => VideoStatus::Pending,
            'progress' => 0,
            'message' => 'Waiting for the Python service',
            'settings_snapshot' => $settings,
        ]);

        try {
            $this->python->startRender($video);
        } catch (Throwable $exception) {
            report($exception);

            $video->update([
                'status' => VideoStatus::Failed,
                'message' => $exception instanceof RuntimeException
                    ? $exception->getMessage()
                    : 'The Python service is not running. Start it and try again.',
            ]);
        }

        return $video;
    }
}
