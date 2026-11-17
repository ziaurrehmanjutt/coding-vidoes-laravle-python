<?php

use App\Models\Category;
use App\Models\Question;
use App\Services\UploadMetadata;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('builds a title, description and hashtags for a numbered question', function () {
    $category = Category::factory()->create(['name' => 'Strings']);
    $question = Question::factory()->create([
        'title' => 'Slicing',
        'language' => 'javascript',
        'category_id' => $category->id,
        'series' => 'challenge',
        'series_number' => 12,
    ]);

    $suggestion = app(UploadMetadata::class)->suggest($question);

    expect($suggestion['upload_title'])->toBe('Challenge 12 - JavaScript Quiz: Slicing')
        ->and($suggestion['upload_description'])->toContain('This is Challenge 12 of the JavaScript quiz series.')
        ->and($suggestion['upload_description'])->toContain('Follow for more JavaScript quizzes!')
        ->and($suggestion['hashtags'])->toBe(['javascript', 'coding', 'programming', 'quiz', 'shorts', 'strings', 'challenge12']);
});

it('leaves out the series and category parts when there are none', function () {
    $question = Question::factory()->create(['title' => 'Plain', 'category_id' => null, 'series' => null, 'series_number' => null]);

    $suggestion = app(UploadMetadata::class)->suggest($question);

    expect($suggestion['upload_title'])->toBe('Python Quiz: Plain')
        ->and($suggestion['upload_description'])->not->toContain('series')
        ->and($suggestion['hashtags'])->toBe(['python', 'coding', 'programming', 'quiz', 'shorts']);
});

it('keeps the title within the 100 characters the platform allows', function () {
    $question = Question::factory()->create(['title' => str_repeat('Very long title ', 12)]);

    $title = app(UploadMetadata::class)->suggest($question)['upload_title'];

    expect(mb_strlen($title))->toBeLessThanOrEqual(100)
        ->and($title)->toEndWith('...');
});

it('works for a question that is not saved yet', function () {
    $category = Category::factory()->create(['name' => 'Recursion']);
    $draft = new Question([
        'title' => 'Draft',
        'language' => 'php',
        'category_id' => $category->id,
        'question_text' => 'What prints?',
        'series' => 'day',
        'series_number' => 3,
    ]);

    $suggestion = app(UploadMetadata::class)->suggest($draft);

    expect($suggestion['upload_title'])->toBe('Day 3 - PHP Quiz: Draft')
        ->and($suggestion['hashtags'])->toContain('php', 'recursion', 'day3');
});
