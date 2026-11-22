<?php

namespace App\Http\Controllers;

use App\Models\Question;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UploadQueueController extends Controller
{
    /**
     * Questions that are not uploaded yet, in posting order.
     */
    public function __invoke(Request $request): View
    {
        $series = $request->validate([
            'series' => ['nullable', Rule::in(array_keys(config('video.series')))],
        ])['series'] ?? null;

        $questions = Question::query()
            ->whereNull('uploaded_at')
            ->when($series, fn ($query, string $series) => $query->where('series', $series))
            ->with(['category', 'completedVideo', 'latestVideo'])
            ->inSequence()
            ->get();

        return view('queue.index', [
            'questions' => $questions,
            'seriesOptions' => config('video.series'),
            'activeSeries' => $series,
            'nextUp' => $questions->first(fn (Question $question) => $question->completedVideo?->isPlayable()),
        ]);
    }
}
