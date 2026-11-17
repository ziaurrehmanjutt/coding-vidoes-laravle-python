<?php

use App\Models\Category;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

/**
 * @return array<string, mixed>
 */
function seriesQuestionData(array $overrides = []): array
{
    return array_merge([
        'title' => 'String slicing',
        'language' => 'python',
        'question_text' => 'What will be the correct output?',
        'code' => 'print("Python"[1:3])',
        'options' => ['yt', 'yth', 'Py', 'th'],
        'correct_option' => 0,
        'explanation' => 'Slicing from 1 to 3 gives yt.',
    ], $overrides);
}

it('gives each new question in a series the next free number', function () {
    $this->post(route('questions.store'), seriesQuestionData(['title' => 'One', 'series' => 'day']));
    $this->post(route('questions.store'), seriesQuestionData(['title' => 'Two', 'series' => 'day']));
    $this->post(route('questions.store'), seriesQuestionData(['title' => 'Three', 'series' => 'challenge']));

    expect(Question::where('title', 'One')->first()->seriesLabel())->toBe('Day 1')
        ->and(Question::where('title', 'Two')->first()->seriesLabel())->toBe('Day 2')
        ->and(Question::where('title', 'Three')->first()->seriesLabel())->toBe('Challenge 1');
});

it('accepts a typed number and offers the following one', function () {
    $this->post(route('questions.store'), seriesQuestionData(['series' => 'day', 'series_number' => 7]));

    $question = Question::sole();

    expect($question->seriesLabel())->toBe('Day 7')
        ->and($question->nextSeriesLabel())->toBe('Day 8');

    $this->post(route('questions.store'), seriesQuestionData(['title' => 'Next', 'series' => 'day']));

    expect(Question::where('title', 'Next')->first()->seriesLabel())->toBe('Day 8');
});

it('does not allow the same number twice in one series but allows it in another', function () {
    Question::factory()->create(['series' => 'day', 'series_number' => 3]);

    $this->post(route('questions.store'), seriesQuestionData(['series' => 'day', 'series_number' => 3]))
        ->assertSessionHasErrors(['series_number' => 'That number is already used in this series.']);

    $this->post(route('questions.store'), seriesQuestionData(['series' => 'challenge', 'series_number' => 3]))
        ->assertSessionHasNoErrors();

    expect(Question::count())->toBe(2);
});

it('lets a question keep its own number when it is edited', function () {
    $question = Question::factory()->create(['series' => 'day', 'series_number' => 4]);

    $this->put(route('questions.update', $question), seriesQuestionData(['series' => 'day', 'series_number' => 4]))
        ->assertSessionHasNoErrors();

    expect($question->refresh()->seriesLabel())->toBe('Day 4');
});

it('rejects an unknown series and numbers outside the allowed range', function (array $overrides, string $errorKey) {
    $this->post(route('questions.store'), seriesQuestionData($overrides))->assertSessionHasErrors($errorKey);
})->with([
    'unknown series' => [['series' => 'week'], 'series'],
    'number too big' => [['series' => 'day', 'series_number' => 10000], 'series_number'],
    'number zero' => [['series' => 'day', 'series_number' => 0], 'series_number'],
]);

it('ignores a number when no series is chosen', function () {
    $this->post(route('questions.store'), seriesQuestionData(['series_number' => 5]))
        ->assertSessionHasNoErrors();

    expect(Question::sole())
        ->series->toBeNull()
        ->series_number->toBeNull();
});

it('clears the number when the series is removed', function () {
    $question = Question::factory()->create(['series' => 'day', 'series_number' => 2]);

    $this->put(route('questions.update', $question), seriesQuestionData(['series' => '', 'series_number' => '2']));

    $question->refresh();

    expect($question->series)->toBeNull()
        ->and($question->series_number)->toBeNull()
        ->and($question->seriesLabel())->toBeNull()
        ->and($question->nextSeriesLabel())->toBeNull();
});

it('fills in the upload title, description and hashtags when they are left empty', function () {
    $category = Category::factory()->create(['name' => 'Lists & Arrays']);

    $this->post(route('questions.store'), seriesQuestionData([
        'series' => 'day',
        'category_id' => $category->id,
    ]));

    $question = Question::sole();

    expect($question->upload_title)->toBe('Day 1 - Python Quiz: String slicing')
        ->and($question->upload_description)->toContain('What will be the correct output?')
        ->and($question->upload_description)->toContain('This is Day 1 of the Python quiz series.')
        ->and($question->hashtags)->toBe(['python', 'coding', 'programming', 'quiz', 'shorts', 'listsarrays', 'day1']);
});

it('keeps upload details that were typed by hand', function () {
    $this->post(route('questions.store'), seriesQuestionData([
        'upload_title' => 'My own title',
        'upload_description' => 'My own description',
        'hashtags' => '#Mine, others',
    ]));

    $question = Question::sole();

    expect($question->upload_title)->toBe('My own title')
        ->and($question->upload_description)->toBe('My own description')
        ->and($question->hashtags)->toBe(['Mine', 'others'])
        ->and($question->hashtagText())->toBe('#Mine #others');
});

it('cleans typed hashtags into a tidy list', function () {
    $this->post(route('questions.store'), seriesQuestionData(['hashtags' => '#python,  #coding!! coding  #fun_times']));

    expect(Question::sole()->hashtags)->toBe(['python', 'coding', 'fun_times']);
});

it('rejects too many hashtags', function () {
    $tags = collect(range(1, 16))->map(fn (int $number) => "tag{$number}")->implode(' ');

    $this->post(route('questions.store'), seriesQuestionData(['hashtags' => $tags]))
        ->assertSessionHasErrors('hashtags');
});

it('regenerates the upload details from the current question', function () {
    $question = Question::factory()->create([
        'title' => 'Fresh title',
        'series' => 'challenge',
        'series_number' => 2,
        'upload_title' => 'Old',
        'upload_description' => 'Old',
        'hashtags' => ['old'],
    ]);

    $this->post(route('questions.upload-details', $question))
        ->assertRedirect(route('questions.edit', $question));

    $question->refresh();

    expect($question->upload_title)->toBe('Challenge 2 - Python Quiz: Fresh title')
        ->and($question->hashtags)->toContain('challenge2');
});

it('shows the series fields and the upload details on the edit form', function () {
    $question = Question::factory()->create(['series' => 'day', 'series_number' => 9, 'upload_title' => 'Saved title']);

    $this->get(route('questions.edit', $question))
        ->assertOk()
        ->assertSee('Number in the series')
        ->assertSee('Saved title')
        ->assertSee('Regenerate title, description and hashtags');
});

it('sends the series labels to Python so they appear in the video', function () {
    Http::preventStrayRequests();
    Http::fake(['*/render' => Http::response(['accepted' => true], 202)]);

    $question = Question::factory()->create(['series' => 'day', 'series_number' => 5]);

    $this->post(route('questions.videos.store', $question));

    Http::assertSent(fn ($request) => $request['question']['series_label'] === 'Day 5'
        && $request['question']['next_series_label'] === 'Day 6');
});

it('filters by series and can order by posting order', function () {
    Question::factory()->create(['title' => 'Day two', 'series' => 'day', 'series_number' => 2]);
    Question::factory()->create(['title' => 'Day one', 'series' => 'day', 'series_number' => 1]);
    Question::factory()->create(['title' => 'Challenge one', 'series' => 'challenge', 'series_number' => 1]);
    Question::factory()->create(['title' => 'Plain question']);

    $this->get(route('questions.index', ['series' => 'day', 'sort' => 'sequence']))
        ->assertSeeInOrder(['Day one', 'Day two'])
        ->assertDontSee('Challenge one')
        ->assertDontSee('Plain question');

    $this->get(route('questions.index', ['series' => 'none']))
        ->assertSee('Plain question')
        ->assertDontSee('Day one');

    $this->get(route('questions.index', ['sort' => 'sequence']))
        ->assertSeeInOrder(['Day one', 'Day two', 'Challenge one', 'Plain question']);
});

it('rejects an unknown series filter', function () {
    $this->get(route('questions.index', ['series' => 'week']))->assertSessionHasErrors('series');
});
