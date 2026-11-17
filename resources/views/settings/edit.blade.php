<x-layouts.app title="Video settings">
    <h1 class="mb-1 text-2xl font-bold tracking-tight">Video settings</h1>
    <p class="mb-6 text-sm text-slate-600 dark:text-slate-400">These apply to every new video. Videos you already made keep the settings they were made with.</p>

    <form method="POST" action="{{ route('settings.update') }}" class="space-y-6"
          data-voice-form data-preview-url="{{ route('settings.preview') }}">
        @csrf
        @method('PUT')

        <section class="panel space-y-5">
            <h2 class="text-lg font-semibold">Voice</h2>

            <div>
                <label for="voice" class="label">Voice</label>
                <div class="flex flex-wrap items-center gap-3">
                    <select id="voice" name="voice" class="field max-w-xs">
                        @foreach ($voices as $value => $label)
                            <option value="{{ $value }}" @selected(old('voice', $setting->voice) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-button variant="secondary" data-preview-button>Preview voice</x-button>
                    <span class="text-sm text-slate-600 dark:text-slate-400" data-preview-status aria-live="polite"></span>
                </div>
                @error('voice') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            @foreach ([
                ['speech_rate', 'Speed', -50, 100, '%'],
                ['speech_pitch', 'Pitch', -50, 50, ' Hz'],
                ['speech_volume', 'Volume', -50, 50, '%'],
            ] as [$name, $label, $min, $max, $unit])
                <div>
                    <label for="{{ $name }}" class="label">
                        {{ $label }}: <output data-range-output data-unit="{{ $unit }}">{{ old($name, $setting->{$name}) }}{{ $unit }}</output>
                    </label>
                    <input id="{{ $name }}" name="{{ $name }}" type="range" min="{{ $min }}" max="{{ $max }}" step="5"
                           value="{{ old($name, $setting->{$name}) }}" class="w-full max-w-md accent-indigo-600">
                    @error($name) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            @endforeach

            <div class="space-y-2">
                <label class="flex items-center gap-3 text-sm">
                    <input type="checkbox" name="narrate_question" value="1" class="size-4 accent-indigo-600"
                           @checked(old('narrate_question', $setting->narrate_question))>
                    Read the question and options aloud
                </label>
                <label class="flex items-center gap-3 text-sm">
                    <input type="checkbox" name="narrate_explanation" value="1" class="size-4 accent-indigo-600"
                           @checked(old('narrate_explanation', $setting->narrate_explanation))>
                    Read the correct answer and explanation aloud
                </label>
                <label class="flex items-center gap-3 text-sm">
                    <input type="checkbox" name="tick_sound" value="1" class="size-4 accent-indigo-600"
                           @checked(old('tick_sound', $setting->tick_sound))>
                    Play a ticking sound while viewers think
                </label>
            </div>
        </section>

        <section class="panel space-y-5">
            <h2 class="text-lg font-semibold">Look and timing</h2>

            <div class="max-w-xs">
                <label for="think_seconds" class="label">Default thinking time (seconds)</label>
                <input id="think_seconds" name="think_seconds" type="number" min="3" max="30" required class="field"
                       value="{{ old('think_seconds', $setting->think_seconds) }}">
                @error('think_seconds') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="max-w-xs">
                <label for="theme" class="label">Theme</label>
                <select id="theme" name="theme" class="field">
                    @foreach ($themes as $value => $label)
                        <option value="{{ $value }}" @selected(old('theme', $setting->theme) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('theme') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="max-w-xs">
                <label for="quality" class="label">Video quality</label>
                <select id="quality" name="quality" class="field">
                    @foreach ($qualities as $value => $label)
                        <option value="{{ $value }}" @selected(old('quality', $setting->quality) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">All qualities are 1080 x 1920. Higher quality keeps text and code sharper but makes a bigger file and takes longer to render.</p>
                @error('quality') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="max-w-xs">
                <label for="channel_name" class="label">Channel name (shown in the video)</label>
                <input id="channel_name" name="channel_name" type="text" maxlength="40" class="field"
                       value="{{ old('channel_name', $setting->channel_name) }}">
                @error('channel_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </section>

        <x-button type="submit">Save settings</x-button>
    </form>
</x-layouts.app>
