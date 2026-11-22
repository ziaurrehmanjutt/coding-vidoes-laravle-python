<?php

namespace App\Http\Controllers;

use App\Enums\VideoStatus;
use App\Models\GeneratedVideo;
use App\Models\Question;
use App\Services\VideoQueue;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GeneratedVideoController extends Controller
{
    public function index(Request $request): View
    {
        $status = VideoStatus::tryFrom((string) $request->query('status'));

        return view('videos.index', [
            'videos' => GeneratedVideo::query()
                ->with('question')
                ->when($status, fn ($query, VideoStatus $status) => $query->where('status', $status))
                ->latest()
                ->latest('id')
                ->get(),
            'activeStatus' => $status,
            'statusCounts' => GeneratedVideo::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    /**
     * Save a pending video with a copy of the current settings, then ask Python to render it.
     */
    public function store(Question $question, VideoQueue $queue): RedirectResponse
    {
        return to_route('videos.show', $queue->start($question));
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
