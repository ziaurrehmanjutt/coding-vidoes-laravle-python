<x-layouts.app title="Videos">
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight">Videos</h1>
        <p class="text-sm text-slate-600 dark:text-slate-400">Everything you have generated. Open one to watch or download it.</p>
    </div>

    @if ($videos->isEmpty())
        <div class="panel py-12 text-center">
            <p class="font-medium">No videos yet.</p>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Pick a question and press "Create video".</p>
            <x-button class="mt-4" :href="route('questions.index')">Go to questions</x-button>
        </div>
    @else
        <div class="grid grid-cols-2 gap-5 sm:grid-cols-3 md:grid-cols-4">
            @foreach ($videos as $video)
                <a href="{{ route('videos.show', $video) }}" class="group block">
                    <div class="relative aspect-[9/16] overflow-hidden rounded-xl bg-slate-900 ring-1 ring-slate-200 transition group-hover:ring-2 group-hover:ring-indigo-500 dark:ring-slate-800">
                        @if ($video->isPlayable())
                            <video src="{{ route('videos.file', $video) }}#t=0.5" preload="metadata" muted playsinline
                                   class="size-full object-cover"></video>
                        @else
                            <div class="flex size-full flex-col items-center justify-center gap-2 p-3 text-center text-sm text-slate-300">
                                @if ($video->status->isFinished())
                                    <span>{{ $video->status === \App\Enums\VideoStatus::Failed ? 'Failed' : 'File missing' }}</span>
                                @else
                                    <span>{{ $video->progress }}%</span>
                                    <span class="text-xs text-slate-400">{{ $video->message }}</span>
                                @endif
                            </div>
                        @endif
                    </div>
                    <div class="mt-2 flex items-center justify-between gap-2">
                        <span class="truncate text-sm font-medium">{{ $video->question->title }}</span>
                        <x-status-badge :status="$video->status" />
                    </div>
                    <span class="text-xs text-slate-500 dark:text-slate-400">{{ $video->created_at->diffForHumans() }}</span>
                </a>
            @endforeach
        </div>
    @endif
</x-layouts.app>
