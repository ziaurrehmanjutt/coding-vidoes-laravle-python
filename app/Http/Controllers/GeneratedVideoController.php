<?php

namespace App\Http\Controllers;

use App\Enums\VideoStatus;
use App\Models\GeneratedVideo;
use App\Models\Question;
use App\Models\VideoSetting;
use App\Services\PythonVideoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class GeneratedVideoController extends Controller
{
    public function index(): View
    {
        return view('videos.index', [
            'videos' => GeneratedVideo::query()->with('question')->latest()->get(),
        ]);
    }

    /**
     * Save a pending video with a copy of the current settings, then ask Python to render it.
     */
    public function store(Question $question, PythonVideoService $python): RedirectResponse
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
            $python->startRender($video);
        } catch (Throwable $exception) {
            report($exception);

            $video->update([
                'status' => VideoStatus::Failed,
                'message' => $exception instanceof RuntimeException
                    ? $exception->getMessage()
                    : 'The Python service is not running. Start it and try again.',
            ]);
        }

        return to_route('videos.show', $video);
    }

    public function show(GeneratedVideo $video): View
    {
        return view('videos.show', ['video' => $video->load('question')]);
    }

    public function status(GeneratedVideo $video): JsonResponse
    {
        return response()->json([
            'status' => $video->status->value,
            'label' => $video->status->label(),
            'progress' => $video->progress,
            'message' => $video->message,
            'finished' => $video->status->isFinished(),
        ]);
    }

    /**
     * Stream the MP4 inline for the player (supports seeking).
     */
    public function file(GeneratedVideo $video): BinaryFileResponse
    {
        abort_unless($video->isPlayable(), 404);

        return response()->file(
            Storage::disk(config('video.disk'))->path($video->path),
            ['Content-Type' => 'video/mp4'],
        );
    }

    public function download(GeneratedVideo $video): StreamedResponse
    {
        abort_unless($video->isPlayable(), 404);

        $name = Str::slug($video->question->title ?: 'python-quiz').'-'.$video->id.'.mp4';

        return Storage::disk(config('video.disk'))->download($video->path, $name);
    }

    public function destroy(GeneratedVideo $video): RedirectResponse
    {
        $video->deleteWithFile();

        return to_route('videos.index')->with('status', 'Video deleted.');
    }
}
