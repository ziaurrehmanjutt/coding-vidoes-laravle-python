<x-layouts.app :title="$project->name" :wide="true">
    <div id="studio"
         data-project="{{ json_encode([
             'name' => $project->name,
             'type' => $project->type->value,
             'category' => $project->category,
             'language' => $project->language,
             'tags' => $project->tags ?? [],
             'document' => $project->document,
             'render_status' => $project->render_status?->value,
             'playable' => $project->isPlayable(),
             'file_url' => $project->isPlayable() ? route('projects.file', $project) : null,
             'download_url' => $project->isPlayable() ? route('projects.download', $project) : null,
         ]) }}"
         data-voices="{{ json_encode($voices) }}"
         data-asset-url="{{ url('projects/'.$project->id.'/assets') }}"
         data-asset-store-url="{{ route('projects.assets.store', $project) }}"
         data-save-url="{{ route('projects.update', $project) }}"
         data-render-url="{{ route('projects.render', $project) }}"
         data-status-url="{{ route('projects.status', $project) }}">

        {{-- Top bar --}}
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <a href="{{ route('projects.index') }}" class="text-sm text-slate-500 hover:text-slate-900 dark:hover:text-slate-100">&larr; Studio</a>
            <input id="name" aria-label="Project name" value="{{ $project->name }}" maxlength="80" class="field !w-64 font-semibold">
            <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                Length
                <input id="duration" type="number" min="1" max="120" step="0.5" value="{{ $project->document['duration'] ?? 12 }}" class="field !w-20 !py-1.5">
                s
            </label>
            <x-button id="undo" variant="secondary" title="Undo (Ctrl+Z)">Undo</x-button>
            <x-button id="redo" variant="secondary" title="Redo (Ctrl+Y)">Redo</x-button>
            <button id="record" type="button" title="Every change you make at the playhead becomes a keyframe">Record motion</button>
            <details class="relative">
                <summary class="cursor-pointer list-none rounded-lg bg-white px-3 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-100 dark:ring-slate-700">Details</summary>
                <div class="absolute left-0 z-20 mt-2 w-80 space-y-3 rounded-xl border border-slate-200 bg-white p-4 shadow-xl dark:border-slate-700 dark:bg-slate-900">
                    <label class="block text-sm"><span class="label">Type</span>
                        <select id="meta-type" class="field">
                            @foreach ($types as $type)
                                <option value="{{ $type['key'] }}" @selected($project->type->value === $type['key'])>{{ $type['label'] }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-sm"><span class="label">Category</span>
                        <select id="meta-category" class="field">
                            <option value="">No category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category }}" @selected($project->category === $category)>{{ $category }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-sm"><span class="label">Language</span>
                        <select id="meta-language" class="field">
                            <option value="">None</option>
                            @foreach (config('video.languages') as $key => $label)
                                <option value="{{ $key }}" @selected($project->language === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-sm"><span class="label">Tags (separate with commas)</span>
                        <input id="meta-tags" class="field" maxlength="300" value="{{ implode(', ', $project->tags ?? []) }}" placeholder="beginner, strings, tips">
                    </label>
                </div>
            </details>
            <span id="save-state" class="text-xs text-slate-500" role="status"></span>

            <div class="ml-auto flex flex-wrap items-center gap-3">
                <span id="render-status" class="text-sm text-slate-600 dark:text-slate-400" role="status"></span>
                <span id="render-links" class="flex gap-3 text-sm"></span>
                <x-button id="render">Make the video (MP4)</x-button>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-[150px_minmax(300px,380px)_minmax(0,1fr)]">
            {{-- Add objects --}}
            <div class="panel !p-3">
                <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Add</h2>
                <div class="grid grid-cols-2 gap-2 lg:grid-cols-1">
                    @foreach ([['text', 'Text'], ['code', 'Typing code'], ['rect', 'Box'], ['ellipse', 'Circle'], ['arrow', 'Arrow'], ['line', 'Line'], ['voice', 'Voice-over']] as [$kind, $label])
                        <button type="button" data-add="{{ $kind }}"
                                class="rounded-lg bg-slate-100 px-3 py-2 text-left text-sm font-medium transition hover:bg-indigo-50 hover:text-indigo-700 dark:bg-slate-800 dark:hover:bg-indigo-950">
                            + {{ $label }}
                        </button>
                    @endforeach
                    <button type="button" onclick="document.getElementById('image-file').click()"
                            class="rounded-lg bg-slate-100 px-3 py-2 text-left text-sm font-medium transition hover:bg-indigo-50 hover:text-indigo-700 dark:bg-slate-800 dark:hover:bg-indigo-950">
                        + Picture
                    </button>
                    <input id="image-file" type="file" accept="image/png,image/jpeg,image/webp,image/gif" class="hidden">
                </div>
                <p class="mt-3 hidden text-xs text-slate-500 lg:block">Tips: Space plays, Delete removes, arrow keys nudge.</p>
            </div>

            {{-- Live preview --}}
            <div>
                <div class="relative mx-auto w-full max-w-[380px] select-none overflow-hidden rounded-xl bg-black shadow-lg ring-1 ring-slate-300 dark:ring-slate-700" style="aspect-ratio: 9 / 16">
                    <canvas id="stage" class="block h-full w-full" style="touch-action: none"></canvas>
                    <div id="overlay" class="pointer-events-none absolute inset-0"></div>
                </div>
                <div class="mx-auto mt-3 flex max-w-[380px] items-center gap-3">
                    <x-button id="play" variant="secondary">Play</x-button>
                    <input id="scrub" type="range" min="0" max="12" step="0.01" value="0" class="flex-1" aria-label="Playhead">
                    <span id="clock" class="w-24 text-right text-xs tabular-nums text-slate-500"></span>
                </div>
            </div>

            {{-- Properties --}}
            <div id="inspector" class="panel max-h-[760px] overflow-y-auto !p-4"></div>
        </div>

        {{-- Timeline --}}
        <div class="panel mt-4 !p-3">
            <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Timeline</h2>
            <div id="timeline" class="relative overflow-x-auto"></div>
            <p class="mt-2 text-xs text-slate-500">
                Drag a bar to change when an object appears; drag its edges to change how long it stays. Yellow diamonds are keyframes. Click the ruler to move the playhead.
            </p>
        </div>
    </div>
</x-layouts.app>
