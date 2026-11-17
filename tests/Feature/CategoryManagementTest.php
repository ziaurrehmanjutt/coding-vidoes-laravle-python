<?php

use App\Models\Category;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists categories with their question counts', function () {
    $category = Category::factory()->create(['name' => 'Recursion']);
    Question::factory()->count(2)->create(['category_id' => $category->id]);

    $this->get(route('categories.index'))
        ->assertOk()
        ->assertSee('Recursion')
        ->assertSee('2 questions');
});

it('adds a category', function () {
    $this->post(route('categories.store'), ['name' => 'Recursion'])
        ->assertRedirect(route('categories.index'));

    expect(Category::sole()->name)->toBe('Recursion');
});

it('rejects an empty or duplicate category name', function (array $data) {
    Category::factory()->create(['name' => 'Recursion']);

    $this->post(route('categories.store'), $data)->assertSessionHasErrors('name');

    expect(Category::count())->toBe(1);
})->with([
    'empty name' => [['name' => '']],
    'duplicate name' => [['name' => 'Recursion']],
    'name too long' => [['name' => str_repeat('a', 61)]],
]);

it('renames a category and allows keeping its own name', function () {
    $category = Category::factory()->create(['name' => 'Old name']);

    $this->put(route('categories.update', $category), ['name' => 'New name'])
        ->assertRedirect(route('categories.index'));

    expect($category->refresh()->name)->toBe('New name');

    $this->put(route('categories.update', $category), ['name' => 'New name'])
        ->assertSessionHasNoErrors();
});

it('does not rename a category to a name that is already taken', function () {
    Category::factory()->create(['name' => 'Taken']);
    $category = Category::factory()->create(['name' => 'Mine']);

    $this->put(route('categories.update', $category), ['name' => 'Taken'])
        ->assertSessionHasErrors('name');

    expect($category->refresh()->name)->toBe('Mine');
});

it('deletes a category but keeps its questions', function () {
    $category = Category::factory()->create();
    $question = Question::factory()->create(['category_id' => $category->id]);

    $this->delete(route('categories.destroy', $category))
        ->assertRedirect(route('categories.index'));

    expect(Category::count())->toBe(0)
        ->and($question->refresh()->category_id)->toBeNull();
});
