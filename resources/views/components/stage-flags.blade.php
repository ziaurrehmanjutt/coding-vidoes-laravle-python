@props(['question'])

@php
    $stage = $question->stage();
    $latest = $question->latestVideo;
    $completed = $question->completedVideo;
    $pill = 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold';
@endphp

<div {{ $attributes->class(['flex flex-wrap items-center gap-2']) }}>
    @if ($completed)
        @if ($completed->isPlayable())
            <span class="{{ $pill }} bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                <span aria-hidden="true">&#127916;</span> Video ready
            </span>
        @else
            <span class="{{ $pill }} bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300">File missing - regenerate</span>
        @endif
    @elseif ($stage === \App\Enums\QuestionStage::Rendering)
        <span class="{{ $pill }} bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300" data-poll-url="{{ route('videos.status', $latest) }}">
            <span class="size-2 animate-pulse rounded-full bg-amber-500" aria-hidden="true"></span>
            <span data-poll-text>{{ $latest->status->label() }} {{ $latest->progress }}%</span>
        </span>
    @elseif ($stage === \App\Enums\QuestionStage::Failed)
        <span class="{{ $pill }} bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300">Render failed</span>
    @else
        <span class="{{ $pill }} bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">No video yet</span>
    @endif

    @if ($question->isUploaded())
        <span class="{{ $pill }} bg-emerald-600 text-white">
            <span aria-hidden="true">&#10003;</span> Uploaded {{ $question->uploaded_at->format('M j') }}
        </span>
    @else
        <span class="{{ $pill }} bg-white text-slate-500 ring-1 ring-slate-300 dark:bg-slate-900 dark:text-slate-400 dark:ring-slate-700">Not uploaded</span>
    @endif
</div>
