<?php

namespace App\Models;

use Database\Factories\VideoSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'voice',
    'speech_rate',
    'speech_pitch',
    'speech_volume',
    'narrate_question',
    'narrate_explanation',
    'tick_sound',
    'think_seconds',
    'theme',
    'quality',
    'channel_name',
])]
class VideoSetting extends Model
{
    /** @use HasFactory<VideoSettingFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'speech_rate' => 'integer',
            'speech_pitch' => 'integer',
            'speech_volume' => 'integer',
            'narrate_question' => 'boolean',
            'narrate_explanation' => 'boolean',
            'tick_sound' => 'boolean',
            'think_seconds' => 'integer',
        ];
    }

    /**
     * The single settings row used for every video.
     */
    public static function current(): self
    {
        return self::query()->firstOrCreate([], config('video.defaults'));
    }
}
