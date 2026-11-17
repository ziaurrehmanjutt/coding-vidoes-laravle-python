<?php

namespace Database\Factories;

use App\Models\VideoSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VideoSetting>
 */
class VideoSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return config('video.defaults');
    }
}
