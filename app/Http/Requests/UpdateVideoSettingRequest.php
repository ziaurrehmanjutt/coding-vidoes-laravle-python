<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVideoSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Unticked checkboxes are not sent by the browser, so treat them as false.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'narrate_question' => $this->boolean('narrate_question'),
            'narrate_explanation' => $this->boolean('narrate_explanation'),
            'tick_sound' => $this->boolean('tick_sound'),
            'comment_prompt' => $this->boolean('comment_prompt'),
            'hook_line' => $this->boolean('hook_line'),
            'mascot' => $this->boolean('mascot'),
            'reveal_effects' => $this->boolean('reveal_effects'),
            'sound_effects' => $this->boolean('sound_effects'),
            'background_music' => $this->boolean('background_music'),
            'answer_voice' => $this->input('answer_voice') ?: null,
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
            'voice' => ['required', Rule::in(array_keys(config('video.voices')))],
            'speech_rate' => ['required', 'integer', 'between:-50,100'],
            'speech_pitch' => ['required', 'integer', 'between:-50,50'],
            'speech_volume' => ['required', 'integer', 'between:-50,50'],
            'narrate_question' => ['boolean'],
            'narrate_explanation' => ['boolean'],
            'tick_sound' => ['boolean'],
            'comment_prompt' => ['boolean'],
            'hook_line' => ['boolean'],
            'mascot' => ['boolean'],
            'reveal_effects' => ['boolean'],
            'sound_effects' => ['boolean'],
            'background_music' => ['boolean'],
            'answer_voice' => ['nullable', Rule::in(array_keys(config('video.voices')))],
            'think_seconds' => ['required', 'integer', 'between:3,30'],
            'theme' => ['required', Rule::in(array_keys(config('video.themes')))],
            'quality' => ['required', Rule::in(array_keys(config('video.qualities')))],
            'channel_name' => ['nullable', 'string', 'max:40'],
        ];
    }
}
