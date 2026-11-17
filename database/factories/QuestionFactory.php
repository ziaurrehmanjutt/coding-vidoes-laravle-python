<?php

namespace Database\Factories;

use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'question_text' => 'What will be the correct output?',
            'code' => "s = \"Python\"\nprint(s[1:5] + s[-2:])",
            'options' => ['ython', 'ythoon', 'Pythoon', 'ythonon'],
            'correct_option' => 1,
            'explanation' => 'Slicing s[1:5] gives "ytho" and s[-2:] gives "on", so the result is "ythoon".',
            'think_seconds' => null,
        ];
    }
}
