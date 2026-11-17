<?php

namespace App\Http\Controllers;

use App\Models\Question;
use Illuminate\Http\RedirectResponse;

class QuestionUploadController extends Controller
{
    /**
     * Mark the question as uploaded, or clear the mark.
     */
    public function __invoke(Question $question): RedirectResponse
    {
        $question->update(['uploaded_at' => $question->isUploaded() ? null : now()]);

        return back()->with('status', $question->isUploaded()
            ? 'Marked as uploaded.'
            : 'Marked as not uploaded.');
    }
}
