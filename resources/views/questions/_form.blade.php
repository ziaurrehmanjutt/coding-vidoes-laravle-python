<form method="POST" action="{{ $action }}" class="panel space-y-6"
      data-predict-form data-predict-url="{{ route('questions.predict') }}">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <label for="title" class="label">Title</label>
        <input id="title" name="title" type="text" required maxlength="120" class="field"
               value="{{ old('title', $question->title) }}" placeholder="Python string slicing">
        @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="language" class="label">Language</label>
            <select id="language" name="language" required class="field">
                @foreach ($languages as $value => $label)
                    <option value="{{ $value }}" @selected(old('language', $question->language ?? 'python') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('language') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="category_id" class="label">Category</label>
            <select id="category_id" name="category_id" class="field">
                <option value="">No category</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) old('category_id', $question->category_id) === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            @error('category_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="sm:col-start-2">
            <label for="new_category" class="label">Or create a new category</label>
            <input id="new_category" name="new_category" type="text" maxlength="60" class="field"
                   value="{{ old('new_category') }}" placeholder="Leave empty to use the category above">
            @error('new_category') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="series" class="label">Series</label>
            <select id="series" name="series" class="field">
                <option value="">No series</option>
                @foreach ($seriesOptions as $value => $label)
                    <option value="{{ $value }}" @selected(old('series', $question->series) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('series') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="series_number" class="label">Number in the series</label>
            <input id="series_number" name="series_number" type="number" min="1" max="9999" class="field"
                   value="{{ old('series_number', $question->series_number) }}" placeholder="Empty = next free number">
            @error('series_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="question_text" class="label">Question shown in the video</label>
        <input id="question_text" name="question_text" type="text" required maxlength="300" class="field"
               value="{{ old('question_text', $question->question_text ?? 'What will be the correct output?') }}">
        @error('question_text') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="code" class="label">Code</label>
        <textarea id="code" name="code" rows="6" maxlength="3000" spellcheck="false" class="field font-mono">{{ old('code', $question->code) }}</textarea>
        @error('code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

        <div class="mt-2 flex flex-wrap items-start gap-3">
            <x-button variant="secondary" data-predict-button>Run the code and pick the answer</x-button>
            <div class="min-w-0 flex-1 text-sm" data-predict-result aria-live="polite"></div>
        </div>
    </div>

    <fieldset>
        <legend class="label">Options (choose the correct one)</legend>
        <div class="space-y-3">
            @foreach (range(0, 3) as $index)
                <div class="flex items-center gap-3">
                    <input type="radio" name="correct_option" value="{{ $index }}" required
                           id="correct_{{ $index }}" data-correct-radio
                           class="size-4 accent-emerald-600"
                           @checked((int) old('correct_option', $question->correct_option ?? 0) === $index)
                           aria-label="Option {{ chr(65 + $index) }} is correct">
                    <span class="w-5 font-bold">{{ chr(65 + $index) }}</span>
                    <input type="text" name="options[{{ $index }}]" required maxlength="120" class="field font-mono"
                           data-option-input value="{{ old('options.'.$index, $question->options[$index] ?? '') }}">
                </div>
                @error('options.'.$index) <p class="ml-12 text-sm text-red-600">{{ $message }}</p> @enderror
            @endforeach
        </div>
        @error('options') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        @error('correct_option') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </fieldset>

    <div>
        <label for="explanation" class="label">Explanation (read aloud after the answer)</label>
        <textarea id="explanation" name="explanation" rows="4" required maxlength="1500" class="field">{{ old('explanation', $question->explanation) }}</textarea>
        @error('explanation') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="max-w-xs">
        <label for="think_seconds" class="label">Thinking time (seconds)</label>
        <input id="think_seconds" name="think_seconds" type="number" min="3" max="30" class="field"
               value="{{ old('think_seconds', $question->think_seconds) }}" placeholder="Use the default from settings">
        @error('think_seconds') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    @php
        $savedTags = old('hashtags', $question->hashtags ?? []);
        $hashtagText = is_array($savedTags)
            ? collect($savedTags)->map(fn ($tag) => '#'.$tag)->implode(' ')
            : $savedTags;
    @endphp

    <fieldset class="space-y-4">
        <legend class="label">Upload details</legend>
        <p class="text-sm text-slate-600 dark:text-slate-400">Leave these empty and they are filled in for you when you save. You can edit them any time.</p>

        <div>
            <label for="upload_title" class="label">Title</label>
            <input id="upload_title" name="upload_title" type="text" maxlength="100" class="field"
                   value="{{ old('upload_title', $question->upload_title) }}">
            @error('upload_title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="upload_description" class="label">Description</label>
            <textarea id="upload_description" name="upload_description" rows="5" maxlength="5000" class="field">{{ old('upload_description', $question->upload_description) }}</textarea>
            @error('upload_description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="hashtags" class="label">Hashtags</label>
            <input id="hashtags" name="hashtags" type="text" class="field" value="{{ $hashtagText }}" placeholder="#python #coding #shorts">
            @error('hashtags') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            @error('hashtags.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        @if ($question->exists)
            <x-button type="submit" variant="secondary" form="regenerate-upload-details">Regenerate title, description and hashtags</x-button>
        @endif
    </fieldset>

    <fieldset class="space-y-3">
        <legend class="label">Publishing</legend>
        <label class="flex items-center gap-3 text-sm">
            <input type="checkbox" name="is_uploaded" value="1" class="size-4 accent-indigo-600"
                   @checked(old('is_uploaded', $question->isUploaded()))>
            This question's video has been uploaded
        </label>
        <div class="max-w-xl">
            <label for="upload_url" class="label">Link to the uploaded video (optional)</label>
            <input id="upload_url" name="upload_url" type="url" maxlength="300" class="field"
                   value="{{ old('upload_url', $question->upload_url) }}" placeholder="https://youtube.com/shorts/...">
            @error('upload_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </fieldset>

    <div class="flex items-center gap-3">
        <x-button type="submit">{{ $submitLabel }}</x-button>
        <x-button variant="secondary" :href="route('questions.index')">Cancel</x-button>
    </div>
</form>

@if ($question->exists)
    <form id="regenerate-upload-details" method="POST" action="{{ route('questions.upload-details', $question) }}">
        @csrf
    </form>
@endif
