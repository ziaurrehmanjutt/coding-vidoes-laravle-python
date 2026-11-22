@php
    $filtering = collect($filters)->filter()->isNotEmpty();
    $chip = 'inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-medium transition';
    $chipOff = 'bg-white text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-700 dark:hover:bg-slate-800';
    $chipOn = 'bg-indigo-600 text-white';
    $withFilter = fn (array $change) => route('projects.index', array_filter(array_merge($filters, $change), fn ($value) => $value !== null && $value !== ''));
@endphp

<x-layouts.app title="Studio">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Studio</h1>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                Build your own videos: type code, move shapes and pictures, write text on a timeline, with a live preview.
            </p>
        </div>
        <p class="text-sm text-slate-500">{{ $projects->count() }} of {{ $total }} {{ str('video')->plural($total) }}</p>
    </div>

    <details class="panel mb-6 !p-5" @if ($total === 0) open @endif>
        <summary class="cursor-pointer text-sm font-semibold">Start a new video</summary>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            @foreach ($templates as $key => $template)
                <form method="POST" action="{{ route('projects.store') }}" class="flex flex-col justify-between gap-3 rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                    @csrf
                    <input type="hidden" name="template" value="{{ $key }}">
                    <div>
                        <h3 class="font-semibold">{{ $template['label'] }}</h3>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $template['description'] }}</p>
                    </div>
                    <x-button type="submit" :variant="$key === 'blank' ? 'secondary' : 'primary'">Use this</x-button>
                </form>
            @endforeach
        </div>
    </details>

    {{-- Search and filters --}}
    <form method="GET" action="{{ route('projects.index') }}" class="mb-4 space-y-4" id="project-filters" data-auto-submit>
        @if ($filters['type'] ?? null)<input type="hidden" name="type" value="{{ $filters['type'] }}">@endif
        @if ($filters['tag'] ?? null)<input type="hidden" name="tag" value="{{ $filters['tag'] }}">@endif

        <div class="flex flex-wrap items-center gap-3">
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search by title or tag..." aria-label="Search videos" class="field !w-72">
            <select name="category" class="field !w-auto" aria-label="Category">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category }}" @selected(($filters['category'] ?? null) === $category)>{{ $category }}</option>
                @endforeach
                <option value="none" @selected(($filters['category'] ?? null) === 'none')>No category</option>
            </select>
            <select name="language" class="field !w-auto" aria-label="Language">
                <option value="">Any language</option>
                @foreach (config('video.languages') as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['language'] ?? null) === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="status" class="field !w-auto" aria-label="Video status">
                <option value="">Any status</option>
                <option value="made" @selected(($filters['status'] ?? null) === 'made')>Video made</option>
                <option value="todo" @selected(($filters['status'] ?? null) === 'todo')>Not made yet</option>
                <option value="rendering" @selected(($filters['status'] ?? null) === 'rendering')>Rendering</option>
            </select>
            <x-button type="submit" variant="secondary">Search</x-button>
            @if ($filtering)
                <a href="{{ route('projects.index') }}" class="text-sm text-indigo-600 underline">Clear filters</a>
            @endif
        </div>

        <div class="flex flex-wrap gap-2" role="group" aria-label="Video type">
            <a href="{{ $withFilter(['type' => null]) }}" class="{{ $chip }} {{ ($filters['type'] ?? null) ? $chipOff : $chipOn }}">All <span class="opacity-70">{{ $total }}</span></a>
            @foreach (\App\Enums\ProjectType::cases() as $type)
                @continue(! ($typeCounts[$type->value] ?? 0))
                <a href="{{ $withFilter(['type' => $type->value]) }}" class="{{ $chip }} {{ ($filters['type'] ?? null) === $type->value ? $chipOn : $chipOff }}">
                    {{ $type->label() }} <span class="opacity-70">{{ $typeCounts[$type->value] }}</span>
                </a>
            @endforeach
        </div>

        @if ($tagCounts)
            <div class="flex flex-wrap items-center gap-1.5" role="group" aria-label="Tags">
                <span class="mr-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Tags</span>
                @foreach ($tagCounts as $tag => $count)
                    <a href="{{ $withFilter(['tag' => ($filters['tag'] ?? null) === $tag ? null : $tag]) }}"
                       class="rounded-md px-2 py-1 text-xs font-medium {{ ($filters['tag'] ?? null) === $tag ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">#{{ $tag }} <span class="opacity-60">{{ $count }}</span></a>
                @endforeach
            </div>
        @endif
    </form>

    {{-- The videos --}}
    @if ($projects->isEmpty())
        <p class="rounded-2xl border border-dashed border-slate-300 px-6 py-10 text-center text-sm text-slate-500 dark:border-slate-700">
            @if ($filtering)
                No videos match these filters. <a href="{{ route('projects.index') }}" class="text-indigo-600 underline">Clear filters</a>
            @else
                No studio videos yet. Open "Start a new video" above.
            @endif
        </p>
    @else
        <ul class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($projects as $project)
                <li class="panel flex gap-4 !p-4">
                    <a href="{{ route('projects.edit', $project) }}" class="shrink-0" aria-label="Open {{ $project->name }}">
                        <div class="flex w-24 items-center justify-center overflow-hidden rounded-lg p-2 text-center text-[10px] font-bold leading-tight text-white ring-1 ring-slate-300 dark:ring-slate-700"
                             style="aspect-ratio: 9 / 16; background: linear-gradient(to bottom, {{ $project->backgroundColors()[0] }}, {{ $project->backgroundColors()[1] }})">
                            {{ str($project->headline())->limit(40) }}
                        </div>
                    </a>

                    <div class="flex min-w-0 flex-1 flex-col">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $project->type->badgeClasses() }}">{{ $project->type->label() }}</span>
                            @if ($project->language)
                                <a href="{{ $withFilter(['language' => $project->language]) }}" class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300">{{ config('video.languages')[$project->language] ?? $project->language }}</a>
                            @endif
                        </div>

                        <a href="{{ route('projects.edit', $project) }}" class="mt-1.5 font-semibold leading-snug hover:text-indigo-600">{{ $project->name }}</a>

                        @if ($project->category)
                            <a href="{{ $withFilter(['category' => $project->category]) }}" class="mt-0.5 text-xs text-slate-500 hover:text-indigo-600">{{ $project->category }}</a>
                        @endif

                        @if ($project->tags)
                            <div class="mt-2 flex flex-wrap gap-1">
                                @foreach ($project->tags as $tag)
                                    <a href="{{ $withFilter(['tag' => $tag]) }}" class="text-xs text-slate-500 hover:text-indigo-600">#{{ $tag }}</a>
                                @endforeach
                            </div>
                        @endif

                        <p class="mt-2 text-xs text-slate-500">
                            {{ $project->document['duration'] ?? 0 }} s &middot; {{ count($project->document['objects'] ?? []) }} objects
                            &middot;
                            @if ($project->isPlayable())
                                <span class="font-semibold text-emerald-700 dark:text-emerald-400">Video made</span>
                            @elseif ($project->render_status === \App\Enums\VideoStatus::Failed)
                                <span class="font-semibold text-red-600">Render failed</span>
                            @elseif ($project->render_status)
                                <span class="font-semibold text-amber-600">{{ $project->render_status->label() }} {{ $project->render_progress }}%</span>
                            @else
                                Not made yet
                            @endif
                        </p>

                        <div class="mt-auto flex flex-wrap items-center gap-2 pt-3">
                            <x-button :href="route('projects.edit', $project)">Open editor</x-button>
                            @if ($project->isPlayable())
                                <x-button variant="secondary" :href="route('projects.download', $project)">Download</x-button>
                            @endif
                            <details class="relative">
                                <summary class="cursor-pointer list-none rounded-lg px-2 py-2 text-sm font-semibold text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800">More</summary>
                                <div class="absolute right-0 z-10 mt-1 w-40 space-y-1 rounded-lg border border-slate-200 bg-white p-2 shadow-lg dark:border-slate-700 dark:bg-slate-900">
                                    <form method="POST" action="{{ route('projects.duplicate', $project) }}">
                                        @csrf
                                        <button type="submit" class="w-full rounded px-2 py-1.5 text-left text-sm hover:bg-slate-100 dark:hover:bg-slate-800">Make a copy</button>
                                    </form>
                                    <form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm('Delete this project and its video?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-full rounded px-2 py-1.5 text-left text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-950">Delete</button>
                                    </form>
                                </div>
                            </details>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</x-layouts.app>
