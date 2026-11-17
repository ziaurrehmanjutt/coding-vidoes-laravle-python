<?php

use App\Models\Category;
use App\Models\GeneratedVideo;
use App\Models\Question;
use Database\Seeders\QuestionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * @return array<string, mixed>
 */
function validQuestionData(array $overrides = []): array
{
    return array_merge([
        'title' => 'String slicing',
        'language' => 'python',
        'category_id' => null,
        'new_category' => null,
        'is_uploaded' => null,
        'upload_url' => null,
        'question_text' => 'What will be the correct output?',
        'code' => "s = \"Python\"\nprint(s[1:5] + s[-2:])",
        'options' => ['ython', 'ythoon', 'Pythoon', 'ythonon'],
        'correct_option' => 1,
        'explanation' => 'ytho plus on gives ythoon.',
        'think_seconds' => null,
    ], $overrides);
}

it('lists questions with the correct option marked', function () {
    Question::factory()->create(['title' => 'Slicing quiz']);

    $this->get(route('questions.index'))
        ->assertOk()
        ->assertSee('Slicing quiz')
        ->assertSee('Create video');
});

it('shows the empty state when there are no questions', function () {
    $this->get(route('questions.index'))
        ->assertOk()
        ->assertSee('No questions yet.');
});

it('stores a question with four options', function () {
    $this->post(route('questions.store'), validQuestionData())
        ->assertRedirect(route('questions.index'));

    $question = Question::sole();

    expect($question->options)->toBe(['ython', 'ythoon', 'Pythoon', 'ythonon'])
        ->and($question->correct_option)->toBe(1)
        ->and($question->correctLetter())->toBe('B')
        ->and($question->correctAnswerText())->toBe('ythoon');
});

it('rejects invalid questions', function (array $overrides, string $errorKey) {
    $this->post(route('questions.store'), validQuestionData($overrides))
        ->assertSessionHasErrors($errorKey);

    expect(Question::count())->toBe(0);
})->with([
    'missing title' => [['title' => ''], 'title'],
    'only three options' => [['options' => ['a', 'b', 'c']], 'options'],
    'duplicate options' => [['options' => ['a', 'a', 'b', 'c']], 'options.1'],
    'correct option out of range' => [['correct_option' => 4], 'correct_option'],
    'missing explanation' => [['explanation' => ''], 'explanation'],
    'think time too short' => [['think_seconds' => 1], 'think_seconds'],
]);

it('renders the edit form with the saved values', function () {
    $question = Question::factory()->create(['title' => 'Edit me']);

    $this->get(route('questions.edit', $question))
        ->assertOk()
        ->assertSee('Edit me')
        ->assertSee('Run the code and pick the answer');
});

it('updates a question', function () {
    $question = Question::factory()->create();

    $this->put(route('questions.update', $question), validQuestionData(['title' => 'Renamed', 'correct_option' => 3]))
        ->assertRedirect(route('questions.index'));

    expect($question->refresh()->title)->toBe('Renamed')
        ->and($question->correct_option)->toBe(3);
});

it('deletes a question together with its videos and files', function () {
    Storage::fake('local');
    Storage::disk('local')->put('videos/video-1.mp4', 'fake');

    $question = Question::factory()->create();
    GeneratedVideo::factory()->completed()->for($question)->create(['path' => 'videos/video-1.mp4']);

    $this->delete(route('questions.destroy', $question))
        ->assertRedirect(route('questions.index'));

    expect(Question::count())->toBe(0)
        ->and(GeneratedVideo::count())->toBe(0);
    Storage::disk('local')->assertMissing('videos/video-1.mp4');
});

it('predicts the output of the code through the Python service', function () {
    Http::preventStrayRequests();
    Http::fake(['*/predict' => Http::response(['ok' => true, 'output' => 'ythoon', 'error' => ''])]);

    $this->postJson(route('questions.predict'), ['code' => 'print("ythoon")'])
        ->assertOk()
        ->assertJson(['ok' => true, 'output' => 'ythoon']);

    Http::assertSent(fn ($request) => $request->hasHeader('X-Api-Token', config('video.python_token'))
        && $request['code'] === 'print("ythoon")');
});

it('requires code to predict an output', function () {
    $this->postJson(route('questions.predict'), ['code' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('code');
});

it('says so when the Python service is not running', function () {
    Http::fake(['*' => fn () => throw new ConnectionException('Connection refused')]);

    $this->postJson(route('questions.predict'), ['code' => 'print(1)'])
        ->assertStatus(503)
        ->assertJson(['message' => 'The Python service is not running.']);
});

it('reports a bad gateway when Python rejects the prediction', function () {
    Http::fake(['*/predict' => Http::response(['detail' => 'boom'], 500)]);

    $this->postJson(route('questions.predict'), ['code' => 'print(1)'])
        ->assertStatus(502);
});

it('seeds valid example questions without duplicating them', function () {
    $this->seed(QuestionSeeder::class);
    $this->seed(QuestionSeeder::class);

    $questions = Question::all();

    expect($questions)->toHaveCount(29)
        ->and($questions->pluck('language')->unique()->sort()->values()->all())->toBe(['javascript', 'php', 'python'])
        ->and($questions->whereNull('category_id'))->toBeEmpty()
        ->and($questions->whereNull('series'))->toBeEmpty()
        ->and($questions->where('series', 'day')->pluck('series_number')->sort()->values()->all())->toBe(range(1, 15))
        ->and($questions->where('series', 'challenge')->pluck('series_number')->sort()->values()->all())->toBe(range(1, 14));

    $questions->each(function (Question $question) {
        expect($question->upload_title)->not->toBeEmpty()
            ->and($question->hashtags)->toContain($question->language)
            ->and($question->options)->toHaveCount(4)
            ->and(array_unique($question->options))->toHaveCount(4)
            ->and($question->correct_option)->toBeBetween(0, 3)
            ->and($question->code)->not->toBeEmpty();
    });
});

it('stores the language, an existing category and the upload details', function () {
    $category = Category::factory()->create();

    $this->post(route('questions.store'), validQuestionData([
        'language' => 'javascript',
        'category_id' => $category->id,
        'is_uploaded' => '1',
        'upload_url' => 'https://youtube.com/shorts/abc123',
    ]))->assertRedirect(route('questions.index'));

    $question = Question::sole();

    expect($question->language)->toBe('javascript')
        ->and($question->languageLabel())->toBe('JavaScript')
        ->and($question->category->is($category))->toBeTrue()
        ->and($question->isUploaded())->toBeTrue()
        ->and($question->upload_url)->toBe('https://youtube.com/shorts/abc123');
});

it('creates a new category from the question form and reuses an existing one', function () {
    $this->post(route('questions.store'), validQuestionData(['new_category' => 'Recursion']));
    $this->post(route('questions.store'), validQuestionData(['title' => 'Second', 'new_category' => 'Recursion']));

    expect(Category::pluck('name')->all())->toBe(['Recursion'])
        ->and(Question::pluck('category_id')->unique()->all())->toHaveCount(1);
});

it('rejects an unknown language, category or upload link', function (array $overrides, string $errorKey) {
    $this->post(route('questions.store'), validQuestionData($overrides))
        ->assertSessionHasErrors($errorKey);
})->with([
    'unknown language' => [['language' => 'klingon'], 'language'],
    'missing category' => [['category_id' => 999], 'category_id'],
    'bad upload link' => [['upload_url' => 'not a url'], 'upload_url'],
]);

it('keeps the first upload date when a question is edited and still uploaded', function () {
    $question = Question::factory()->create(['uploaded_at' => now()->subDays(3)]);
    $originalDate = $question->uploaded_at->toDateTimeString();

    $this->put(route('questions.update', $question), validQuestionData(['is_uploaded' => '1']));

    expect($question->refresh()->uploaded_at->toDateTimeString())->toBe($originalDate);

    $this->put(route('questions.update', $question), validQuestionData());

    expect($question->refresh()->isUploaded())->toBeFalse();
});

it('toggles the uploaded mark', function () {
    $question = Question::factory()->create();

    $this->patch(route('questions.uploaded', $question))->assertRedirect();
    expect($question->refresh()->isUploaded())->toBeTrue();

    $this->patch(route('questions.uploaded', $question))->assertRedirect();
    expect($question->refresh()->isUploaded())->toBeFalse();
});

it('sends the chosen language when predicting an output', function () {
    Http::fake(['*/predict' => Http::response(['ok' => true, 'supported' => true, 'output' => '53 2', 'error' => ''])]);

    $this->postJson(route('questions.predict'), ['code' => 'console.log("5" + 3, "5" - 3)', 'language' => 'javascript'])
        ->assertOk()
        ->assertJson(['output' => '53 2']);

    Http::assertSent(fn ($request) => $request['language'] === 'javascript');
});

it('rejects an unknown language when predicting an output', function () {
    $this->postJson(route('questions.predict'), ['code' => 'print(1)', 'language' => 'klingon'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('language');
});
