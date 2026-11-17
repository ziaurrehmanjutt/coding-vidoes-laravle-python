@php
    $isWorking = ! $video->status->isFinished();
    $question = $video->question;
@endphp

<x-layouts.app :title="$question->title">
    <div class="mb-6">
        <a href="{{ route('videos.index') }}" class="text-sm text-indigo-600 hover:underline dark:text-indigo-400">&larr; All videos</a>
        <h1 class="mt-2 text-2xl font-bold tracking-tight">{{ $question->title }}</h1>
    </div>

    <div class="grid gap-8 md:grid-cols-[minmax(0,22rem)_1fr]">
        <div>
            @if ($video->isPlayable())
                <video controls playsinline preload="metadata" src="{{ route('videos.file', $video) }}"
                       class="aspect-[9/16] w-full rounded-2xl bg-black shadow-lg"></video>
            @else
                <div class="flex aspect-[9/16] w-full flex-col items-center justify-center gap-4 rounded-2xl bg-slate-900 p-6 text-center text-slate-200"
                     @if ($isWorking)
                         data-video-progress
                         data-status-url="{{ route('videos.status', $video) }}"
                     @endif>
                    @if ($isWorking)
                        <p class="text-4xl font-bold" data-progress-percent>{{ $video->progress }}%</p>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-slate-700" role="progressbar"
                             aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $video->progress }}" data-progress-track>
                            <div class="h-full rounded-full bg-indigo-500 transition-all" style="width: {{ $video->progress }}%" data-progress-bar></div>
                        </div>
                        <p class="text-sm text-slate-300" data-progress-message>{{ $video->message }}</p>
                        <p class="text-xs text-slate-400">This page updates by itself.</p>
                    @elseif ($video->status === \App\Enums\VideoStatus::Failed)
                        <p class="text-lg font-semibold text-red-300">Video failed</p>
                        <p class="text-sm text-slate-300">{{ $video->message }}</p>
                    @else
                        <p class="text-lg font-semibold">The video file is missing</p>
                        <p class="text-sm text-slate-300">Create it again to get a new one.</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="panel space-y-3">
                <div class="flex items-center gap-3">
                    <x-status-badge :status="$video->status" />
                    @if ($video->duration_seconds)
                        <span class="text-sm text-slate-600 dark:text-slate-400">{{ number_format($video->duration_seconds, 0) }} seconds</span>
                    @endif
                </div>

                @if ($video->status === \App\Enums\VideoStatus::Completed && $video->message && $video->message !== 'Video is ready')
                    <p class="text-sm text-amber-700 dark:text-amber-300">{{ $video->message }}</p>
                @endif

                <p class="text-sm text-slate-600 dark:text-slate-400">{{ $question->question_text }}</p>
                @if ($question->code)
                    <pre class="overflow-x-auto rounded-lg bg-slate-900 p-4 text-sm text-slate-100"><code>{{ $question->code }}</code></pre>
                @endif
                <p class="text-sm"><span class="font-semibold">Correct answer:</span>
                    {{ $question->correctLetter() }} &mdash; <span class="font-mono">{{ $question->correctAnswerText() }}</span></p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                @if ($video->isPlayable())
                    <x-button :href="route('videos.download', $video)">Download MP4</x-button>
                @endif

                <form method="POST" action="{{ route('questions.videos.store', $question) }}">
                    @csrf
                    <x-button type="submit" variant="secondary">
                        {{ $video->status === \App\Enums\VideoStatus::Failed ? 'Try again' : 'Create another video' }}
                    </x-button>
                </form>

                <x-button variant="secondary" :href="route('questions.edit', $question)">Edit question</x-button>

                <form method="POST" action="{{ route('videos.destroy', $video) }}"
                      onsubmit="return confirm('Delete this video?')">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="danger">Delete</x-button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.app>
