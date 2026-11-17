<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexQuestionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'language' => ['nullable', Rule::in(array_keys(config('video.languages')))],
            'category' => ['nullable', Rule::when(
                $this->input('category') !== 'none',
                ['integer', 'exists:categories,id'],
            )],
            'series' => ['nullable', Rule::in(['none', ...array_keys(config('video.series'))])],
            'uploaded' => ['nullable', Rule::in(['yes', 'no'])],
            'video' => ['nullable', Rule::in(['with', 'without'])],
            'sort' => ['nullable', Rule::in(['sequence'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
