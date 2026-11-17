<?php

namespace App\Models;

use App\Enums\VideoStatus;
use Database\Factories\GeneratedVideoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'question_id',
    'status',
    'progress',
    'message',
    'path',
    'duration_seconds',
    'settings_snapshot',
])]
class GeneratedVideo extends Model
{
    /** @use HasFactory<GeneratedVideoFactory> */
    use HasFactory;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => VideoStatus::class,
            'progress' => 'integer',
            'duration_seconds' => 'float',
            'settings_snapshot' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * Where Laravel and Python agree the file lives, relative to the disk root.
     */
    public function expectedPath(): string
    {
        return config('video.directory').'/video-'.$this->getKey().'.mp4';
    }

    public function fileExists(): bool
    {
        return $this->path !== null
            && Storage::disk(config('video.disk'))->exists($this->path);
    }

    public function isPlayable(): bool
    {
        return $this->status === VideoStatus::Completed && $this->fileExists();
    }
}
