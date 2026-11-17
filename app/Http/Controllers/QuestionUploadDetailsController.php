<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Services\UploadMetadata;
use Illuminate\Http\RedirectResponse;

class QuestionUploadDetailsController extends Controller
{
    /**
     * Replace the upload title, description and hashtags with fresh suggestions.
     */
    public function __invoke(Question $question, UploadMetadata $metadata): RedirectResponse
    {
        $question->update($metadata->suggest($question));

        return to_route('questions.edit', $question)
            ->with('status', 'Upload title, description and hashtags were regenerated.');
    }
}
