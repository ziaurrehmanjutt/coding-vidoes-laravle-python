@props(['status'])

<span @class([
    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold',
    'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300' => $status === \App\Enums\VideoStatus::Pending,
    'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' => $status === \App\Enums\VideoStatus::Rendering,
    'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' => $status === \App\Enums\VideoStatus::Completed,
    'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300' => $status === \App\Enums\VideoStatus::Failed,
])>
    {{ $status->label() }}
</span>
