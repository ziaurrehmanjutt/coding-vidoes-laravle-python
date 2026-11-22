@props(['question'])

{{-- The buttons that make sense for where this question's video is right now. --}}
@php
    $stage = $question->stage();
    $completed = $question->completedVideo;
    $latest = $question->latestVideo;
    $playable = $completed?->isPlayable();
@endphp

@if ($playable)
    <x-button :href="route('videos.download', $completed)">Download MP4</x-button>
    <x-button variant="secondary" :href="route('videos.show', $completed)">Watch</x-button>
    <form method="POST" action="{{ route('questions.videos.store', $question) }}"
          onsubmit="return confirm('Make a new video from the current question and settings? The old video is kept.')">
        @csrf
        <x-button type="submit" variant="secondary">Regenerate</x-button>
    </form>
@elseif ($stage === \App\Enums\QuestionStage::Rendering)
    <x-button :href="route('videos.show', $latest)">View progress</x-button>
@else
    <form method="POST" action="{{ route('questions.videos.store', $question) }}">
        @csrf
        <x-button type="submit">{{ $stage === \App\Enums\QuestionStage::Failed || $completed ? 'Try again' : 'Create video' }}</x-button>
    </form>
    @if ($stage === \App\Enums\QuestionStage::Failed && $latest)
        <x-button variant="secondary" :href="route('videos.show', $latest)">See why</x-button>
    @endif
@endif
