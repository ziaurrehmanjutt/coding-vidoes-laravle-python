<?php

namespace App\Http\Requests;

use App\Enums\ProjectType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    /**
     * Object types the editor and the Python renderer understand.
     *
     * @var list<string>
     */
    public const OBJECT_TYPES = ['text', 'code', 'rect', 'ellipse', 'arrow', 'image', 'voice'];

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
        $color = ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/'];

        return [
            'name' => ['required', 'string', 'max:80'],
            'type' => ['sometimes', Rule::enum(ProjectType::class)],
            'category' => ['nullable', 'string', Rule::in(config('studio.categories'))],
            'language' => ['nullable', Rule::in(array_keys(config('video.languages')))],
            'tags' => ['nullable', 'array', 'max:12'],
            'tags.*' => ['nullable', 'string', 'max:30'],
            'document' => ['required', 'array'],
            'document.background' => $color,
            'document.background2' => $color,
            'document.music' => ['nullable', 'boolean'],
            'document.duration' => ['required', 'numeric', 'between:1,120'],
            'document.objects' => ['present', 'array', 'max:80'],
            'document.objects.*.id' => ['required', 'string', 'max:40'],
            'document.objects.*.type' => ['required', Rule::in(self::OBJECT_TYPES)],
            'document.objects.*.name' => ['nullable', 'string', 'max:60'],
            'document.objects.*.start' => ['nullable', 'numeric', 'between:0,120'],
            'document.objects.*.duration' => ['nullable', 'numeric', 'between:0.1,120'],
            'document.objects.*.text' => ['nullable', 'string', 'max:2000'],
            'document.objects.*.code' => ['nullable', 'string', 'max:4000'],
            'document.objects.*.fill' => $color,
            'document.objects.*.stroke' => $color,
            'document.objects.*.color' => $color,
            'document.objects.*.asset' => ['nullable', 'string', 'regex:/^[A-Za-z0-9]+\.(png|jpg|jpeg|webp|gif)$/'],
            'document.objects.*.tracks' => ['nullable', 'array'],
        ];
    }
}
