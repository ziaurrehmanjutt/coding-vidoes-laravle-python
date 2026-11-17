<?php

use App\Models\Category;
use App\Models\GeneratedVideo;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->strings = Category::factory()->create(['name' => 'Strings']);
    $this->lists = Category::factory()->create(['name' => 'Lists']);

    $this->slicing = Question::factory()->create([
        'title' => 'Slicing quiz',
        'language' => 'python',
        'category_id' => $this->strings->id,
        'code' => 'print(s[1:5])',
        'uploaded_at' => now(),
    ]);
    $this->mapping = Question::factory()->create([
        'title' => 'Map quiz',
        'language' => 'javascript',
        'category_id' => $this->lists->id,
        'code' => 'console.log([1, 2].map(n => n * 2))',
    ]);
    $this->loose = Question::factory()->create([
        'title' => 'Loose quiz',
        'language' => 'php',
        'category_id' => null,
        'explanation' => 'Contains the secret word banana.',
    ]);
});

it('shows every question when no filter is chosen', function () {
    $this->get(route('questions.index'))
        ->assertOk()
        ->assertSeeInOrder(['Loose quiz', 'Map quiz', 'Slicing quiz'])
        ->assertSee('3 questions');
});

it('searches title, code and explanation', function (string $term, array $expected) {
    $response = $this->get(route('questions.index', ['q' => $term]))->assertOk();

    foreach (['Slicing quiz', 'Map quiz', 'Loose quiz'] as $title) {
        in_array($title, $expected, true)
            ? $response->assertSee($title)
            : $response->assertDontSee($title);
    }
})->with([
    'by title' => ['Map', ['Map quiz']],
    'by code' => ['print(s[1:5])', ['Slicing quiz']],
    'by explanation' => ['banana', ['Loose quiz']],
    'no match' => ['zzz-nothing', []],
]);

it('filters by language', function () {
    $this->get(route('questions.index', ['language' => 'javascript']))
        ->assertOk()
        ->assertSee('Map quiz')
        ->assertDontSee('Slicing quiz')
        ->assertDontSee('Loose quiz');
});

it('filters by category and by uncategorized', function () {
    $this->get(route('questions.index', ['category' => $this->strings->id]))
        ->assertSee('Slicing quiz')
        ->assertDontSee('Map quiz')
        ->assertDontSee('Loose quiz');

    $this->get(route('questions.index', ['category' => 'none']))
        ->assertSee('Loose quiz')
        ->assertDontSee('Slicing quiz')
        ->assertDontSee('Map quiz');
});

it('filters by upload status', function () {
    $this->get(route('questions.index', ['uploaded' => 'yes']))
        ->assertSee('Slicing quiz')
        ->assertDontSee('Map quiz');

    $this->get(route('questions.index', ['uploaded' => 'no']))
        ->assertDontSee('Slicing quiz')
        ->assertSee('Map quiz')
        ->assertSee('Loose quiz');
});

it('filters by whether a finished video exists', function () {
    GeneratedVideo::factory()->completed()->for($this->mapping)->create();
    GeneratedVideo::factory()->failed()->for($this->loose)->create();

    $this->get(route('questions.index', ['video' => 'with']))
        ->assertSee('Map quiz')
        ->assertDontSee('Slicing quiz')
        ->assertDontSee('Loose quiz');

    $this->get(route('questions.index', ['video' => 'without']))
        ->assertDontSee('Map quiz')
        ->assertSee('Slicing quiz')
        ->assertSee('Loose quiz');
});

it('combines filters', function () {
    $this->get(route('questions.index', ['language' => 'python', 'uploaded' => 'yes', 'q' => 'slic']))
        ->assertSee('Slicing quiz')
        ->assertSee('1 of 3 questions match');

    $this->get(route('questions.index', ['language' => 'python', 'uploaded' => 'no']))
        ->assertDontSee('Slicing quiz')
        ->assertSee('No questions match these filters.');
});

it('rejects filter values that do not exist', function (array $query, string $errorKey) {
    $this->get(route('questions.index', $query))->assertSessionHasErrors($errorKey);
})->with([
    'unknown language' => [['language' => 'klingon'], 'language'],
    'unknown category' => [['category' => 999], 'category'],
    'bad upload value' => [['uploaded' => 'maybe'], 'uploaded'],
    'bad video value' => [['video' => 'maybe'], 'video'],
]);

it('paginates and keeps the filters in the page links', function () {
    Question::factory()->count(12)->create(['language' => 'go', 'category_id' => null]);

    $this->get(route('questions.index', ['language' => 'go']))
        ->assertOk()
        ->assertSee('language=go', false)
        ->assertSee('page=2', false);

    $this->get(route('questions.index', ['language' => 'go', 'page' => 2]))
        ->assertOk()
        ->assertSee('12 of 15 questions match');
});

it('shows the language, category and upload badges on each question', function () {
    $this->get(route('questions.index'))
        ->assertSee('JavaScript')
        ->assertSee('Strings')
        ->assertSee('Uploaded')
        ->assertSee('Mark as uploaded')
        ->assertSee('Mark not uploaded');
});
