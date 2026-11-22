<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkQuestionRequest;
use App\Models\Question;
use App\Services\PythonVideoService;
use App\Services\VideoQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;

class QuestionBulkController extends Controller
{
    /**
     * Apply one action to every ticked question.
     */
    public function __invoke(BulkQuestionRequest $request, VideoQueue $queue, PythonVideoService $python): RedirectResponse
    {
        $questions = Question::query()->whereIn('id', $request->validated('ids'))->get();

        return match ($request->validated('action')) {
            'create_videos' => $this->createVideos($questions, $queue, $python),
            'mark_uploaded' => $this->setUploaded($questions, true),
            default => $this->setUploaded($questions, false),
        };
    }

    /**
     * @param  Collection<int, Question>  $questions
     */
    private function createVideos($questions, VideoQueue $queue, PythonVideoService $python): RedirectResponse
    {
        if (! $python->isReachable()) {
            return back()->with('error', 'The Python service is not running. Start it (composer run dev) and try again.');
        }

        $questions->each(fn (Question $question) => $queue->start($question));

        return back()->with('status', "Started {$questions->count()} ".str('video')->plural($questions->count()).'. They render one after another.');
    }

    /**
     * @param  Collection<int, Question>  $questions
     */
    private function setUploaded($questions, bool $uploaded): RedirectResponse
    {
        Question::query()
            ->whereIn('id', $questions->modelKeys())
            ->when($uploaded, fn ($query) => $query->whereNull('uploaded_at'))
            ->update(['uploaded_at' => $uploaded ? now() : null]);

        return back()->with('status', $uploaded
            ? "Marked {$questions->count()} as uploaded."
            : "Marked {$questions->count()} as not uploaded.");
    }
}
