@php
    use App\Enums\QuestionStage;

    $keep = collect($filters)->except(['stage', 'page'])->all() + ($sortBySequence ? ['sort' => 'sequence'] : []);
    $advancedActive = collect($filters)->except(['q', 'stage'])->isNotEmpty() || $sortBySequence;
    $missingCount = $stageCounts['draft'] + $stageCounts['failed'];

    $tabs = [
        [null, 'All', $stageCounts['all']],
        ...collect(QuestionStage::cases())->map(fn (QuestionStage $stage) => [$stage->value, $stage->label(), $stageCounts[$stage->value]])->all(),
    ];
@endphp

<x-layouts.app title="Questions">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Questions</h1>
            <p class="text-sm text-slate-600 dark:text-slate-400">Write a quiz question, make its video, download it, then mark it as uploaded.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            @if ($missingCount > 0)
                <form method="POST" action="{{ route('questions.missing-videos') }}"
                      onsubmit="return confirm('Make a video for all {{ $missingCount }} questions that have none yet?')">
                    @csrf
                    <x-button type="submit" variant="secondary">Make all missing videos ({{ $missingCount }})</x-button>
                </form>
            @endif
            <x-button variant="secondary" :href="route('categories.index')">Categories</x-button>
            <x-button :href="route('questions.create')">+ Add question</x-button>
        </div>
    </div>

    <nav class="mb-4 flex flex-wrap gap-2" aria-label="Question stage">
        @foreach ($tabs as [$value, $label, $count])
            <a href="{{ route('questions.index', $keep + ($value ? ['stage' => $value] : [])) }}"
               @class([
                   'inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold ring-1 transition',
                   'bg-indigo-600 text-white ring-indigo-600' => $activeStage === $value,
                   'bg-white text-slate-700 ring-slate-300 hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-200 dark:ring-slate-700 dark:hover:bg-slate-800' => $activeStage !== $value,
               ])
               @if ($activeStage === $value) aria-current="page" @endif>
                {{ $label }}
                <span @class([
                    'rounded-full px-2 py-0.5 text-xs',
                    'bg-white/25 text-white' => $activeStage === $value,
                    'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' => $activeStage !== $value,
                ])>{{ $count }}</span>
            </a>
        @endforeach
    </nav>

    <form method="GET" action="{{ route('questions.index') }}" class="panel mb-6 space-y-4 !p-4" role="search" data-auto-submit>
        @if ($activeStage)
            <input type="hidden" name="stage" value="{{ $activeStage }}">
        @endif

        <div class="flex flex-wrap items-center gap-3">
            <div class="min-w-64 flex-1">
                <label for="q" class="sr-only">Search</label>
                <input id="q" name="q" type="search" class="field" maxlength="100"
                       value="{{ $filters['q'] ?? '' }}" placeholder="Search title, question, code or explanation...">
            </div>
            <x-button type="submit">Search</x-button>
            @if ($filters !== [])
                <x-button variant="secondary" :href="route('questions.index')">Clear filters</x-button>
            @endif
        </div>

        <details @open($advancedActive) class="group">
            <summary class="cursor-pointer text-sm font-semibold text-indigo-600 select-none dark:text-indigo-400">
                More filters
                @if ($advancedActive)
                    <span class="ml-1 rounded-full bg-indigo-100 px-2 py-0.5 text-xs text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300">on</span>
                @endif
            </summary>

            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
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
        </details>

        <p class="text-sm text-slate-600 dark:text-slate-400" aria-live="polite">
            @if ($filters !== [])
                {{ $questions->total() }} of {{ $totalQuestions }} {{ Str::plural('question', $totalQuestions) }} match
            @else
                {{ $totalQuestions }} {{ Str::plural('question', $totalQuestions) }}
            @endif
        </p>
    </form>

    <form id="bulk-form" method="POST" action="{{ route('questions.bulk') }}" class="hidden">@csrf</form>

    @if ($questions->isNotEmpty())
        <label class="mb-3 flex w-fit items-center gap-2 text-sm font-medium text-slate-600 dark:text-slate-300">
            <input type="checkbox" class="size-4 accent-indigo-600" data-bulk-all>
            Select all on this page
        </label>
    @endif

    <div class="space-y-4">
        @forelse ($questions as $question)
            @php $stage = $question->stage(); @endphp

            <article id="question-{{ $question->id }}" @class(['panel border-l-4 !p-5', $stage->edgeClasses()])>
                <div class="flex items-start gap-3">
                    <input type="checkbox" form="bulk-form" name="ids[]" value="{{ $question->id }}"
                           class="mt-1.5 size-4 shrink-0 accent-indigo-600" aria-label="Select {{ $question->title }}" data-bulk-item>

                    <div class="min-w-0 flex-1">
                        <div class="mb-1 flex flex-wrap items-center gap-2">
                            @if ($question->seriesLabel())
                                <a href="{{ route('questions.index', ['series' => $question->series, 'sort' => 'sequence']) }}"
                                   class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-800 hover:bg-amber-200 dark:bg-amber-950 dark:text-amber-300 dark:hover:bg-amber-900">
                                    {{ $question->seriesLabel() }}
                                </a>
                            @endif
                            <a href="{{ route('questions.index', ['language' => $question->language]) }}"
                               class="rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-semibold text-indigo-800 hover:bg-indigo-200 dark:bg-indigo-950 dark:text-indigo-300 dark:hover:bg-indigo-900">
                                {{ $question->languageLabel() }}
                            </a>
                            @if ($question->category)
                                <a href="{{ route('questions.index', ['category' => $question->category_id]) }}"
                                   class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                                    {{ $question->category->name }}
                                </a>
                            @endif
                        </div>

                        <h2 class="text-lg font-semibold leading-snug">{{ $question->title }}</h2>
                        <p class="truncate text-sm text-slate-600 dark:text-slate-400">{{ $question->question_text }}</p>
                    </div>

                    <x-stage-flags :question="$question" class="hidden max-w-xs justify-end sm:flex" />
                </div>

                <x-stage-flags :question="$question" class="mt-3 sm:hidden" />

                <details class="mt-3">
                    <summary class="cursor-pointer text-sm font-medium text-slate-600 select-none hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400">
                        Show code and answers
                    </summary>

                    @if ($question->code)
                        <pre class="mt-3 overflow-x-auto rounded-lg bg-slate-900 p-4 text-sm leading-relaxed text-slate-100"><code>{{ $question->code }}</code></pre>
                    @endif

                    <ol class="mt-3 grid gap-2 sm:grid-cols-2">
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
                </details>

                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <x-video-actions :question="$question" />

                    <form method="POST" action="{{ route('questions.uploaded', $question) }}">
                        @csrf
                        @method('PATCH')
                        <x-button type="submit" variant="secondary">
                            {{ $question->isUploaded() ? 'Mark not uploaded' : 'Mark as uploaded' }}
                        </x-button>
                    </form>

                    @if ($question->isUploaded() && $question->upload_url)
                        <x-button variant="secondary" :href="$question->upload_url" target="_blank" rel="noopener noreferrer">Open on YouTube</x-button>
                    @endif

                    <x-button variant="secondary" :href="route('questions.edit', $question)">Edit</x-button>

                    <details class="relative ml-auto">
                        <summary class="inline-flex cursor-pointer items-center rounded-lg px-3 py-2 text-sm font-semibold text-slate-500 select-none hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800">More</summary>
                        <div class="absolute right-0 z-10 mt-1 w-52 rounded-lg bg-white p-1 shadow-lg ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-700">
                            @if ($question->videos_count > 0)
                                <p class="px-3 py-2 text-xs text-slate-500 dark:text-slate-400">{{ $question->videos_count }} {{ Str::plural('video', $question->videos_count) }} made</p>
                            @endif
                            <form method="POST" action="{{ route('questions.destroy', $question) }}"
                                  onsubmit="return confirm('Delete this question and its videos?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full rounded-md px-3 py-2 text-left text-sm font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950">Delete question</button>
                            </form>
                        </div>
                    </details>
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

    <div class="pointer-events-none fixed inset-x-0 bottom-4 z-20 hidden px-4" data-bulk-bar>
        <div class="pointer-events-auto mx-auto flex max-w-3xl flex-wrap items-center gap-3 rounded-2xl bg-slate-900 px-5 py-3 text-sm text-white shadow-xl ring-1 ring-white/10">
            <span class="font-semibold" data-bulk-count>0 selected</span>
            <span class="ml-auto flex flex-wrap items-center gap-2">
                <button type="submit" form="bulk-form" name="action" value="create_videos"
                        class="rounded-lg bg-indigo-500 px-3 py-1.5 font-semibold hover:bg-indigo-400">Create videos</button>
                <button type="submit" form="bulk-form" name="action" value="mark_uploaded"
                        class="rounded-lg bg-white/10 px-3 py-1.5 font-semibold hover:bg-white/20">Mark as uploaded</button>
                <button type="submit" form="bulk-form" name="action" value="mark_not_uploaded"
                        class="rounded-lg bg-white/10 px-3 py-1.5 font-semibold hover:bg-white/20">Undo uploaded</button>
            </span>
        </div>
    </div>
</x-layouts.app>
