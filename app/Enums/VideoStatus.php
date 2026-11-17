<?php

namespace App\Enums;

enum VideoStatus: string
{
    case Pending = 'pending';
    case Rendering = 'rendering';
    case Completed = 'completed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Waiting',
            self::Rendering => 'Rendering',
            self::Completed => 'Ready',
            self::Failed => 'Failed',
        };
    }

    public function isFinished(): bool
    {
        return in_array($this, [self::Completed, self::Failed], true);
    }
}
