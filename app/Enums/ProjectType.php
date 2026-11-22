<?php

namespace App\Enums;

/**
 * The kind of video a studio project makes. Used for the badge and the filter on the Studio page.
 */
enum ProjectType: string
{
    case Code = 'code';
    case Compare = 'compare';
    case Explain = 'explain';
    case Tips = 'tips';
    case Pictures = 'pictures';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Code => 'Typing code',
            self::Compare => 'Compare',
            self::Explain => 'Explain',
            self::Tips => 'Quick tips',
            self::Pictures => 'Pictures',
            self::Other => 'Other',
        };
    }

    /**
     * Tailwind classes for the small coloured badge.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Code => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
            self::Compare => 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300',
            self::Explain => 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300',
            self::Tips => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
            self::Pictures => 'bg-pink-100 text-pink-800 dark:bg-pink-950 dark:text-pink-300',
            self::Other => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
        };
    }
}
