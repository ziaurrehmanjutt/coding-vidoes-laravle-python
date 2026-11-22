<?php

namespace App\Http\Requests;

use App\Enums\ProjectType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexProjectRequest extends FormRequest
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
            'type' => ['nullable', Rule::enum(ProjectType::class)],
            'category' => ['nullable', Rule::in(['none', ...config('studio.categories')])],
            'language' => ['nullable', Rule::in(array_keys(config('video.languages')))],
            'tag' => ['nullable', 'string', 'max:30'],
            'status' => ['nullable', Rule::in(['made', 'todo', 'rendering'])],
        ];
    }
}
