<x-layouts.app title="Upload queue">
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight">Upload queue</h1>
        <p class="text-sm text-slate-600 dark:text-slate-400">Everything not uploaded yet, in posting order. Copy the details, upload the video, then mark it as uploaded.</p>
    </div>

    <nav class="mb-6 flex flex-wrap gap-2" aria-label="Series">
        <a href="{{ route('queue.index') }}" @class([
            'rounded-lg px-3 py-1.5 text-sm font-medium ring-1',
            'bg-indigo-600 text-white ring-indigo-600' => $activeSeries === null,
            'bg-white text-slate-700 ring-slate-300 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700' => $activeSeries !== null,
        ])>All</a>
        @foreach ($seriesOptions as $value => $label)
            <a href="{{ route('queue.index', ['series' => $value]) }}" @class([
                'rounded-lg px-3 py-1.5 text-sm font-medium ring-1',
                'bg-indigo-600 text-white ring-indigo-600' => $activeSeries === $value,
                'bg-white text-slate-700 ring-slate-300 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700' => $activeSeries !== $value,
            ])>{{ $label }}</a>
        @endforeach
    </nav>

    @if ($nextUp)
        <div class="mb-6 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-900 dark:border-indigo-900 dark:bg-indigo-950 dark:text-indigo-200">
            Next to upload: <strong>{{ $nextUp->seriesLabel() ?? $nextUp->title }}</strong>
            @if ($nextUp->seriesLabel()) &mdash; {{ $nextUp->title }} @endif
        </div>
    @endif

    <div class="space-y-5">
        @forelse ($questions as $question)
            <article class="panel space-y-4" id="question-{{ $question->id }}">
                <div class="flex flex-wrap items-center gap-2">
                    @if ($question->seriesLabel())
                        <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-950 dark:text-amber-300">{{ $question->seriesLabel() }}</span>
                    @endif
                    <span class="rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-semibold text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300">{{ $question->languageLabel() }}</span>
                    @if ($question->category)
                        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ $question->category->name }}</span>
                    @endif
                </div>

                <div class="flex flex-wrap items-start justify-between gap-3">
                    <h2 class="text-lg font-semibold">{{ $question->title }}</h2>
                    <x-stage-flags :question="$question" />
                </div>

                <div class="space-y-3">
                    @foreach ([
                        ['title', 'Title', $question->upload_title, 'input'],
                        ['description', 'Description', $question->upload_description, 'textarea'],
                        ['hashtags', 'Hashtags', $question->hashtagText(), 'input'],
                    ] as [$key, $label, $value, $kind])
                        <div>
                            <div class="mb-1 flex items-center justify-between">
                                <label for="{{ $key }}-{{ $question->id }}" class="label !mb-0">{{ $label }}</label>
                                <x-button variant="secondary" class="!px-3 !py-1 text-xs"
                                          data-copy-target="#{{ $key }}-{{ $question->id }}">Copy</x-button>
                            </div>
                            @if ($kind === 'textarea')
                                <textarea id="{{ $key }}-{{ $question->id }}" readonly rows="4" class="field">{{ $value }}</textarea>
                            @else
                                <input id="{{ $key }}-{{ $question->id }}" readonly type="text" class="field" value="{{ $value }}">
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <x-video-actions :question="$question" />

                    <form method="POST" action="{{ route('questions.uploaded', $question) }}">
                        @csrf
                        @method('PATCH')
                        <x-button type="submit" variant="secondary">Mark as uploaded</x-button>
                    </form>

                    <x-button variant="secondary" :href="route('questions.edit', $question)">Edit details</x-button>
                </div>
            </article>
        @empty
            <div class="panel py-12 text-center">
                <p class="font-medium">Nothing waiting to be uploaded.</p>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Add questions or clear the series filter.</p>
            </div>
        @endforelse
    </div>
</x-layouts.app>
