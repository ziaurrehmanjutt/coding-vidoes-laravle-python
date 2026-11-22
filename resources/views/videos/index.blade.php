@php
    use App\Enums\VideoStatus;

    $total = $statusCounts->sum();
    $tabs = [
        [null, 'All', $total],
        ...collect(VideoStatus::cases())->map(fn (VideoStatus $status) => [$status->value, $status->label(), $statusCounts[$status->value] ?? 0])->all(),
    ];
@endphp

<x-layouts.app title="Videos">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Videos</h1>
            <p class="text-sm text-slate-600 dark:text-slate-400">Everything you have generated. Watch, download or make a fresh version.</p>
        </div>
        <x-button variant="secondary" :href="route('queue.index')">Open upload queue</x-button>
    </div>

    <nav class="mb-6 flex flex-wrap gap-2" aria-label="Video status">
        @foreach ($tabs as [$value, $label, $count])
            <a href="{{ route('videos.index', $value ? ['status' => $value] : []) }}"
               @class([
                   'inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold ring-1 transition',
                   'bg-indigo-600 text-white ring-indigo-600' => ($activeStatus?->value) === $value,
                   'bg-white text-slate-700 ring-slate-300 hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-200 dark:ring-slate-700 dark:hover:bg-slate-800' => ($activeStatus?->value) !== $value,
               ])>
                {{ $label }}
                <span @class([
                    'rounded-full px-2 py-0.5 text-xs',
                    'bg-white/25 text-white' => ($activeStatus?->value) === $value,
                    'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' => ($activeStatus?->value) !== $value,
                ])>{{ $count }}</span>
            </a>
        @endforeach
    </nav>

    @if ($videos->isEmpty())
        <div class="panel py-12 text-center">
            <p class="font-medium">{{ $activeStatus ? 'No videos with this status.' : 'No videos yet.' }}</p>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Pick a question and press "Create video".</p>
            <x-button class="mt-4" :href="route('questions.index')">Go to questions</x-button>
        </div>
    @else
        <div class="grid grid-cols-2 gap-5 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
            @foreach ($videos as $video)
                <div class="group">
                    <a href="{{ route('videos.show', $video) }}" class="block">
                        <div class="relative aspect-[9/16] overflow-hidden rounded-xl bg-slate-900 ring-1 ring-slate-200 transition group-hover:ring-2 group-hover:ring-indigo-500 dark:ring-slate-800">
                            @if ($video->isPlayable())
                                <video src="{{ route('videos.file', $video) }}#t=0.5" preload="metadata" muted playsinline
                                       class="size-full object-cover"></video>
                            @else
                                <div class="flex size-full flex-col items-center justify-center gap-2 p-3 text-center text-sm text-slate-300">
                                    @if ($video->status->isFinished())
                                        <span>{{ $video->status === VideoStatus::Failed ? 'Failed' : 'File missing' }}</span>
                                    @else
                                        <span>{{ $video->progress }}%</span>
                                        <span class="text-xs text-slate-400">{{ $video->message }}</span>
                                    @endif
                                </div>
                            @endif

                            @if ($video->question->isUploaded())
                                <span class="absolute left-2 top-2 rounded-full bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white shadow">&#10003; Uploaded</span>
                            @endif
                            @if ($video->question->seriesLabel())
                                <span class="absolute bottom-2 left-2 rounded-full bg-black/60 px-2.5 py-1 text-xs font-bold text-white">{{ $video->question->seriesLabel() }}</span>
                            @endif
                        </div>
                        <div class="mt-2 flex items-center justify-between gap-2">
                            <span class="truncate text-sm font-medium">{{ $video->question->title }}</span>
                            <x-status-badge :status="$video->status" />
                        </div>
                        <span class="text-xs text-slate-500 dark:text-slate-400">{{ $video->created_at->diffForHumans() }}</span>
                    </a>

                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        @if ($video->isPlayable())
                            <x-button :href="route('videos.download', $video)" class="!px-3 !py-1.5 text-xs">Download</x-button>
                        @endif
                        <form method="POST" action="{{ route('questions.videos.store', $video->question) }}"
                              onsubmit="return confirm('Make a new video from the current question and settings?')">
                            @csrf
                            <x-button type="submit" variant="secondary" class="!px-3 !py-1.5 text-xs">{{ $video->status === VideoStatus::Failed ? 'Try again' : 'Regenerate' }}</x-button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-layouts.app>
