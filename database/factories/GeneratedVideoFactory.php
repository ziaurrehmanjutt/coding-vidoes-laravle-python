<?php

namespace Database\Factories;

use App\Enums\VideoStatus;
use App\Models\GeneratedVideo;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GeneratedVideo>
 */
class GeneratedVideoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'status' => VideoStatus::Pending,
            'progress' => 0,
            'message' => null,
            'path' => null,
            'duration_seconds' => null,
            'settings_snapshot' => config('video.defaults'),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => VideoStatus::Completed,
            'progress' => 100,
            'message' => 'Video is ready',
            'duration_seconds' => 30,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => VideoStatus::Failed,
            'message' => 'Renderer crashed',
        ]);
    }
}
