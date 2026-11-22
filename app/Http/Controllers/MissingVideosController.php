<?php

namespace App\Http\Controllers;

use App\Enums\QuestionStage;
use App\Models\Question;
use App\Services\PythonVideoService;
use App\Services\VideoQueue;
use Illuminate\Http\RedirectResponse;

class MissingVideosController extends Controller
{
    /**
     * Start a video for every question that has none yet, or whose last try failed.
     */
    public function __invoke(VideoQueue $queue, PythonVideoService $python): RedirectResponse
    {
        $questions = Question::query()->with(['completedVideo', 'latestVideo'])->inSequence()->get()
            ->filter(fn (Question $question) => in_array($question->stage(), [QuestionStage::Draft, QuestionStage::Failed], true));

        if ($questions->isEmpty()) {
            return back()->with('status', 'Every question already has a video.');
        }

        if (! $python->isReachable()) {
            return back()->with('error', 'The Python service is not running. Start it (composer run dev) and try again.');
        }

        $questions->each(fn (Question $question) => $queue->start($question));

        return back()->with('status', "Started {$questions->count()} ".str('video')->plural($questions->count()).'. They render one after another.');
    }
}
