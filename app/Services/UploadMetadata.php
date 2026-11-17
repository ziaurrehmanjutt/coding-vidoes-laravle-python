<?php

namespace App\Services;

use App\Models\Question;
use Illuminate\Support\Str;

/**
 * Suggests the title, description and hashtags to use when uploading a question's video.
 */
class UploadMetadata
{
    private const MAX_TITLE_LENGTH = 100;

    /**
     * @return array{upload_title: string, upload_description: string, hashtags: list<string>}
     */
    public function suggest(Question $question): array
    {
        $language = $question->languageLabel();
        $seriesLabel = $question->seriesLabel();

        return [
            'upload_title' => $this->title($question, $language, $seriesLabel),
            'upload_description' => $this->description($question, $language, $seriesLabel),
            'hashtags' => $this->hashtags($question),
        ];
    }

    private function title(Question $question, string $language, ?string $seriesLabel): string
    {
        $title = $seriesLabel === null
            ? "{$language} Quiz: {$question->title}"
            : "{$seriesLabel} - {$language} Quiz: {$question->title}";

        return Str::limit($title, self::MAX_TITLE_LENGTH - 3, '...');
    }

    private function description(Question $question, string $language, ?string $seriesLabel): string
    {
        $lines = [
            $question->question_text,
            'Think about it, then comment your answer (A, B, C or D) before the timer ends!',
            '',
        ];

        if ($seriesLabel !== null) {
            $lines[] = "This is {$seriesLabel} of the {$language} quiz series.";
        }

        $lines[] = "Follow for more {$language} quizzes!";

        return implode("\n", $lines);
    }

    /**
     * @return list<string>
     */
    private function hashtags(Question $question): array
    {
        $tags = [
            $question->language,
            'coding',
            'programming',
            'quiz',
            'shorts',
            $question->category !== null ? Str::slug($question->category->name, '') : null,
            $question->series !== null && $question->series_number !== null
                ? Str::slug($question->seriesLabel(), '')
                : null,
        ];

        return array_values(array_unique(array_filter($tags)));
    }
}
