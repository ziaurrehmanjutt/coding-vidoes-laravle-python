<?php

namespace App\Models;

use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'title',
    'question_text',
    'code',
    'options',
    'correct_option',
    'explanation',
    'think_seconds',
])]
class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'options' => 'array',
            'correct_option' => 'integer',
            'think_seconds' => 'integer',
        ];
    }

    /**
     * @return HasMany<GeneratedVideo, $this>
     */
    public function videos(): HasMany
    {
        return $this->hasMany(GeneratedVideo::class);
    }

    public function correctLetter(): string
    {
        return chr(ord('A') + $this->correct_option);
    }

    public function correctAnswerText(): string
    {
        return (string) ($this->options[$this->correct_option] ?? '');
    }
}
