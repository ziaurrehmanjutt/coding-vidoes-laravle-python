@props(['variant' => 'primary', 'href' => null])

@php
    $classes = 'inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:focus-visible:ring-offset-slate-950 '.match ($variant) {
        'secondary' => 'bg-white text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-100 dark:ring-slate-700 dark:hover:bg-slate-700',
        'danger' => 'bg-white text-red-600 ring-1 ring-red-200 hover:bg-red-50 dark:bg-slate-800 dark:text-red-400 dark:ring-red-900 dark:hover:bg-red-950',
        default => 'bg-indigo-600 text-white hover:bg-indigo-500',
    };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class([$classes]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'button'])->class([$classes]) }}>{{ $slot }}</button>
@endif
