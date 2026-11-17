<?php

namespace Database\Seeders;

use App\Models\Question;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    /**
     * Seed the example question from the brief.
     */
    public function run(): void
    {
        Question::query()->firstOrCreate(
            ['title' => 'Python string slicing'],
            [
                'question_text' => 'What will be the correct output?',
                'code' => "s = \"Python\"\n\nprint(s[1:5] + s[-2:])",
                'options' => ['ython', 'ythoon', 'Pythoon', 'ythonon'],
                'correct_option' => 1,
                'explanation' => 's[1:5] takes the characters at index 1 to 4, which is "ytho". '
                    .'s[-2:] takes the last two characters, which is "on". '
                    .'Joining them gives "ythoon".',
            ],
        );
    }
}
