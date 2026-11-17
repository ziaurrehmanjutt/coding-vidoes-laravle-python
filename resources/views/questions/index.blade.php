<x-layouts.app title="Questions">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Questions</h1>
            <p class="text-sm text-slate-600 dark:text-slate-400">Add a quiz question, then turn it into a vertical Shorts video.</p>
        </div>
        <div class="flex items-center gap-3">
            <x-button variant="secondary" :href="route('categories.index')">Categories</x-button>
            <x-button :href="route('questions.create')">Add question</x-button>
        </div>
    </div>

    <form method="GET" action="{{ route('questions.index') }}" class="panel mb-6 space-y-4" role="search" data-auto-submit>
        <div>
            <label for="q" class="label">Search</label>
            <input id="q" name="q" type="search" class="field" maxlength="100"
                   value="{{ $filters['q'] ?? '' }}" placeholder="Search title, question, code or explanation">
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label for="language" class="label">Language</label>
                <select id="language" name="language" class="field">
                    <option value="">All languages</option>
                    @foreach ($languages as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['language'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="category" class="label">Category</label>
                <select id="category" name="category" class="field">
                    <option value="">All categories</option>
                    <option value="none" @selected(($filters['category'] ?? '') === 'none')>Uncategorized</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) ($filters['category'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="series" class="label">Series</label>
                <select id="series" name="series" class="field">
                    <option value="">All</option>
                    <option value="none" @selected(($filters['series'] ?? '') === 'none')>Not in a series</option>
                    @foreach ($seriesOptions as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['series'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="uploaded" class="label">Upload status</label>
                <select id="uploaded" name="uploaded" class="field">
                    <option value="">Any</option>
                    <option value="yes" @selected(($filters['uploaded'] ?? '') === 'yes')>Uploaded</option>
                    <option value="no" @selected(($filters['uploaded'] ?? '') === 'no')>Not uploaded yet</option>
                </select>
            </div>

            <div>
                <label for="video" class="label">Video</label>
                <select id="video" name="video" class="field">
                    <option value="">Any</option>
                    <option value="with" @selected(($filters['video'] ?? '') === 'with')>Has a video</option>
                    <option value="without" @selected(($filters['video'] ?? '') === 'without')>No video yet</option>
                </select>
            </div>

            <div>
                <label for="sort" class="label">Order</label>
                <select id="sort" name="sort" class="field">
                    <option value="">Newest first</option>
                    <option value="sequence" @selected($sortBySequence)>Posting order (Day 1, Day 2...)</option>
                </select>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <x-button type="submit">Search</x-button>
            @if ($filters !== [])
                <x-button variant="secondary" :href="route('questions.index')">Clear filters</x-button>
            @endif
            <p class="text-sm text-slate-600 dark:text-slate-400" aria-live="polite">
                @if ($filters !== [])
                    {{ $questions->total() }} of {{ $totalQuestions }} {{ Str::plural('question', $totalQuestions) }} match
                @else
                    {{ $totalQuestions }} {{ Str::plural('question', $totalQuestions) }}
                @endif
            </p>
        </div>
    </form>

    <div class="space-y-5">
        @forelse ($questions as $question)
            <article class="panel">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="mb-2 flex flex-wrap items-center gap-2">
                            <a href="{{ route('questions.index', ['language' => $question->language]) }}"
                               class="rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-semibold text-indigo-800 hover:bg-indigo-200 dark:bg-indigo-950 dark:text-indigo-300 dark:hover:bg-indigo-900">
                                {{ $question->languageLabel() }}
                            </a>

                            @if ($question->seriesLabel())
                                <a href="{{ route('questions.index', ['series' => $question->series, 'sort' => 'sequence']) }}"
                                   class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800 hover:bg-amber-200 dark:bg-amber-950 dark:text-amber-300 dark:hover:bg-amber-900">
                                    {{ $question->seriesLabel() }}
                                </a>
                            @endif

                            @if ($question->category)
                                <a href="{{ route('questions.index', ['category' => $question->category_id]) }}"
                                   class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                                    {{ $question->category->name }}
                                </a>
                            @endif

                            @if ($question->isUploaded())
                                <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                    Uploaded {{ $question->uploaded_at->format('M j, Y') }}
                                </span>
                                @if ($question->upload_url)
                                    <a href="{{ $question->upload_url }}" target="_blank" rel="noopener noreferrer"
                                       class="text-xs text-indigo-600 hover:underline dark:text-indigo-400">Open link</a>
                                @endif
                            @endif
                        </div>

                        <h2 class="text-lg font-semibold">{{ $question->title }}</h2>
                        <p class="text-sm text-slate-600 dark:text-slate-400">{{ $question->question_text }}</p>
                    </div>

                    @if ($question->latestVideo)
                        <a href="{{ route('videos.show', $question->latestVideo) }}" class="flex items-center gap-2 text-sm text-indigo-600 hover:underline dark:text-indigo-400">
                            Latest video
                            <x-status-badge :status="$question->latestVideo->status" />
                        </a>
                    @endif
                </div>

                @if ($question->code)
                    <pre class="mt-4 overflow-x-auto rounded-lg bg-slate-900 p-4 text-sm leading-relaxed text-slate-100"><code>{{ $question->code }}</code></pre>
                @endif

                <ol class="mt-4 grid gap-2 sm:grid-cols-2">
                    @foreach ($question->options as $index => $option)
                        <li @class([
                            'flex items-center gap-3 rounded-lg px-3 py-2 text-sm ring-1',
                            'bg-emerald-50 text-emerald-900 ring-emerald-300 dark:bg-emerald-950 dark:text-emerald-200 dark:ring-emerald-800' => $index === $question->correct_option,
                            'ring-slate-200 dark:ring-slate-700' => $index !== $question->correct_option,
                        ])>
                            <span class="font-bold">{{ chr(65 + $index) }}</span>
                            <span class="font-mono">{{ $option }}</span>
                            @if ($index === $question->correct_option)
                                <span class="ml-auto text-xs font-semibold uppercase">Correct</span>
                            @endif
                        </li>
                    @endforeach
                </ol>

                <div class="mt-5 flex flex-wrap items-center gap-3">
                    <form method="POST" action="{{ route('questions.videos.store', $question) }}">
                        @csrf
                        <x-button type="submit">Create video</x-button>
                    </form>

                    <x-button variant="secondary" :href="route('questions.edit', $question)">Edit</x-button>

                    <form method="POST" action="{{ route('questions.uploaded', $question) }}">
                        @csrf
                        @method('PATCH')
                        <x-button type="submit" variant="secondary">
                            {{ $question->isUploaded() ? 'Mark not uploaded' : 'Mark as uploaded' }}
                        </x-button>
                    </form>

                    <form method="POST" action="{{ route('questions.destroy', $question) }}"
                          onsubmit="return confirm('Delete this question and its videos?')">
                        @csrf
                        @method('DELETE')
                        <x-button type="submit" variant="danger">Delete</x-button>
                    </form>

                    @if ($question->videos_count > 0)
                        <span class="text-sm text-slate-500 dark:text-slate-400">{{ $question->videos_count }} {{ Str::plural('video', $question->videos_count) }}</span>
                    @endif
                </div>
            </article>
        @empty
            <div class="panel py-12 text-center">
                @if ($filters !== [])
                    <p class="font-medium">No questions match these filters.</p>
                    <x-button class="mt-4" variant="secondary" :href="route('questions.index')">Clear filters</x-button>
                @else
                    <p class="font-medium">No questions yet.</p>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Add your first question to make a video.</p>
                    <x-button class="mt-4" :href="route('questions.create')">Add question</x-button>
                @endif
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $questions->links() }}
    </div>
</x-layouts.app>
