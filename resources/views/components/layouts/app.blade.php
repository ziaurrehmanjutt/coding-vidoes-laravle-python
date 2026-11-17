@props(['title' => 'Quiz Video Maker'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
    <header class="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-4 px-4 py-4">
            <a href="{{ route('questions.index') }}" class="text-lg font-bold tracking-tight">
                Quiz Video Maker
            </a>

            <nav class="flex items-center gap-1 text-sm font-medium" aria-label="Main">
                @foreach ([
                    ['questions.index', 'questions.*', 'Questions'],
                    ['categories.index', 'categories.*', 'Categories'],
                    ['queue.index', 'queue.*', 'Upload queue'],
                    ['videos.index', 'videos.*', 'Videos'],
                    ['settings.edit', 'settings.*', 'Video settings'],
                ] as [$route, $pattern, $label])
                    <a href="{{ route($route) }}"
                       @class([
                           'rounded-lg px-3 py-2 transition',
                           'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' => request()->routeIs($pattern),
                           'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' => ! request()->routeIs($pattern),
                       ])
                       @if (request()->routeIs($pattern)) aria-current="page" @endif>
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-8">
        @if (session('status'))
            <div role="status" class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">
                {{ session('status') }}
            </div>
        @endif

        {{ $slot }}
    </main>
</body>
</html>
