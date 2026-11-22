<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexQuestionRequest;
use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\UpdateQuestionRequest;
use App\Models\Category;
use App\Models\Question;
use App\Services\UploadMetadata;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;

class QuestionController extends Controller
{
    public function index(IndexQuestionRequest $request): View
    {
        $filters = $request->safe()->except('page');
        $sortBySequence = ($filters['sort'] ?? null) === 'sequence';
        unset($filters['sort']);

        $questions = Question::query()
            ->with(['category', 'latestVideo', 'completedVideo'])
            ->withCount('videos')
            ->filter($filters)
            ->when(
                $sortBySequence,
                fn ($query) => $query->inSequence(),
                fn ($query) => $query->latest()->latest('id'),
            )
            ->paginate(10)
            ->withQueryString();

        return view('questions.index', [
            'questions' => $questions,
            'filters' => array_filter($filters, filled(...)),
            'sortBySequence' => $sortBySequence,
            'seriesOptions' => config('video.series'),
            'categories' => Category::query()->orderBy('name')->get(),
            'languages' => config('video.languages'),
            'totalQuestions' => Question::count(),
            'stageCounts' => Question::stageCounts(),
            'activeStage' => $filters['stage'] ?? null,
        ]);
    }

    public function create(): View
    {
        return view('questions.create', $this->formData(new Question));
    }

    public function store(StoreQuestionRequest $request, UploadMetadata $metadata): RedirectResponse
    {
        Question::create($this->attributes($request, $metadata));

        return to_route('questions.index')->with('status', 'Question saved. You can create its video now.');
    }

    public function edit(Question $question): View
    {
        return view('questions.edit', $this->formData($question));
    }

    public function update(UpdateQuestionRequest $request, Question $question, UploadMetadata $metadata): RedirectResponse
    {
        $question->update($this->attributes($request, $metadata, $question));

        return to_route('questions.index')->with('status', 'Question updated.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $question->load('videos')->videos->each->deleteWithFile();
        $question->delete();

        return to_route('questions.index')->with('status', 'Question deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Question $question): array
    {
        return [
            'question' => $question,
            'categories' => Category::query()->orderBy('name')->get(),
            'languages' => config('video.languages'),
            'seriesOptions' => config('video.series'),
        ];
    }

    /**
     * Turn the validated form into question attributes: create the new category
     * if one was typed, and keep the original upload date when still uploaded.
     *
     * @return array<string, mixed>
     */
    private function attributes(StoreQuestionRequest $request, UploadMetadata $metadata, ?Question $question = null): array
    {
        $data = Arr::except($request->validated(), ['new_category', 'is_uploaded']);

        if (filled($request->validated('new_category'))) {
            $data['category_id'] = Category::firstOrCreate(['name' => trim($request->validated('new_category'))])->id;
        }

        $data['uploaded_at'] = $request->boolean('is_uploaded')
            ? ($question?->uploaded_at ?? now())
            : null;

        if (blank($data['series'] ?? null)) {
            $data['series'] = null;
            $data['series_number'] = null;
        } elseif (blank($data['series_number'] ?? null)) {
            $data['series_number'] = Question::nextSeriesNumber($data['series']);
        }

        foreach ($metadata->suggest(new Question($data)) as $field => $value) {
            if (blank($data[$field] ?? null)) {
                $data[$field] = $value;
            }
        }

        return $data;
    }
}
