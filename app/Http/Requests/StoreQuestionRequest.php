<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * An unticked checkbox is not sent by the browser, so treat it as false.
     * Hashtags are typed as one line ("#python, coding") and become a clean list.
     */
    protected function prepareForValidation(): void
    {
        $typed = $this->input('hashtags');

        $tags = collect(is_array($typed) ? $typed : preg_split('/[\s,]+/', (string) $typed, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($tag) => preg_replace('/[^\p{L}\p{N}_]/u', '', ltrim((string) $tag, '#')))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->merge([
            'is_uploaded' => $this->boolean('is_uploaded'),
            'hashtags' => $tags === [] ? null : $tags,
            'series_number' => blank($this->input('series')) ? null : $this->input('series_number'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'language' => ['required', Rule::in(array_keys(config('video.languages')))],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'series' => ['nullable', Rule::in(array_keys(config('video.series')))],
            'series_number' => [
                'nullable',
                'integer',
                'between:1,9999',
                Rule::unique('questions', 'series_number')
                    ->where('series', $this->input('series'))
                    ->ignore($this->route('question')),
            ],
            'new_category' => ['nullable', 'string', 'max:60'],
            'question_text' => ['required', 'string', 'max:300'],
            'code' => ['nullable', 'string', 'max:3000'],
            'options' => ['required', 'array', 'size:4'],
            'options.*' => ['required', 'string', 'max:120', 'distinct'],
            'correct_option' => ['required', 'integer', 'between:0,3'],
            'explanation' => ['required', 'string', 'max:1500'],
            'think_seconds' => ['nullable', 'integer', 'between:3,30'],
            'is_uploaded' => ['boolean'],
            'upload_url' => ['nullable', 'url', 'max:300'],
            'hook' => ['nullable', 'string', 'max:70'],
            'upload_title' => ['nullable', 'string', 'max:100'],
            'upload_description' => ['nullable', 'string', 'max:5000'],
            'hashtags' => ['nullable', 'array', 'max:15'],
            'hashtags.*' => ['string', 'max:40'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'series_number.unique' => 'That number is already used in this series.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'options.0' => 'option A',
            'options.1' => 'option B',
            'options.2' => 'option C',
            'options.3' => 'option D',
            'correct_option' => 'correct answer',
        ];
    }
}
