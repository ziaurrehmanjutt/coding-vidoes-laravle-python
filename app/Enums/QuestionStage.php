<?php

namespace App\Enums;

/**
 * Where a question is on its way from idea to a posted video.
 */
enum QuestionStage: string
{
    case Draft = 'draft';
    case Rendering = 'rendering';
    case Failed = 'failed';
    case Ready = 'ready';
    case Uploaded = 'uploaded';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'To make',
            self::Rendering => 'Rendering',
            self::Failed => 'Failed',
            self::Ready => 'Ready to upload',
            self::Uploaded => 'Uploaded',
        };
    }

    /**
     * Tailwind classes for the coloured edge of a question card.
     */
    public function edgeClasses(): string
    {
        return match ($this) {
            self::Draft => 'border-l-slate-300 dark:border-l-slate-700',
            self::Rendering => 'border-l-amber-400',
            self::Failed => 'border-l-red-500',
            self::Ready => 'border-l-indigo-500',
            self::Uploaded => 'border-l-emerald-500',
        };
    }
}
