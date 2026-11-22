<?php

namespace App\Services;

use App\Enums\ProjectType;

/**
 * Starting points for the studio editor. Every template is a normal project document.
 */
class ProjectTemplates
{
    /**
     * @return array<string, array{label: string, description: string}>
     */
    public static function options(): array
    {
        return [
            'code' => ['label' => 'Type some code', 'description' => 'A title and a code window that types itself.'],
            'flow' => ['label' => 'Move things around', 'description' => 'A box slides to another point and an arrow draws itself.'],
            'compare' => ['label' => 'Compare two things', 'description' => 'Two labels side by side, A against B.'],
            'tip' => ['label' => 'Quick tips list', 'description' => 'A title and points that slide in one by one.'],
            'blank' => ['label' => 'Blank', 'description' => 'Start from nothing and add your own objects.'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function document(string $template): array
    {
        $objects = match ($template) {
            'code' => [
                self::text('title', 'Title', 'What does this print?', 90, 160, 900, 220, ['size' => 84, 'align' => 'center', 'enter' => 'slide', 'duration' => 12]),
                self::code('code', 'Code', "numbers = [1, 2, 3]\nfor n in numbers:\n    print(n * 2)", 70, 520, 940, 520, ['start' => 0.6, 'duration' => 11.4, 'size' => 42, 'cps' => 14, 'enter' => 'pop']),
                self::text('answer', 'Answer', '2  4  6', 90, 1250, 900, 160, ['size' => 110, 'align' => 'center', 'color' => '#4ade80', 'start' => 7, 'duration' => 5, 'enter' => 'pop']),
            ],
            'flow' => [
                self::text('title', 'Title', 'How a variable is stored', 90, 150, 900, 220, ['size' => 76, 'align' => 'center', 'duration' => 12]),
                self::rect('memory', 'Memory', 640, 760, 340, 340, ['fill' => '#1e293b', 'stroke' => '#38bdf8', 'strokeWidth' => 8, 'radius' => 36, 'duration' => 12]),
                self::text('memory-label', 'Memory label', 'memory', 640, 1130, 340, 90, ['size' => 46, 'align' => 'center', 'color' => '#38bdf8', 'duration' => 12]),
                self::text('box', 'x = 5', 'x = 5', 100, 860, 300, 140, [
                    'size' => 58, 'align' => 'center', 'valign' => 'middle', 'fill' => '#2563eb', 'radius' => 30, 'duration' => 12, 'enter' => 'pop',
                    'tracks' => [
                        'x' => [['t' => 1.5, 'v' => 100, 'ease' => 'ease-in-out'], ['t' => 3.5, 'v' => 660]],
                    ],
                ]),
                self::arrow('arrow', 'Arrow', 540, 1500, 540, 1250, ['stroke' => '#facc15', 'start' => 4, 'duration' => 8]),
            ],
            'compare' => [
                self::text('title', 'Title', 'Which one is better?', 90, 150, 900, 200, ['size' => 80, 'align' => 'center', 'duration' => 12]),
                self::text('left', 'Left', 'Python', 60, 520, 440, 150, ['size' => 64, 'align' => 'center', 'valign' => 'middle', 'fill' => '#2563eb', 'radius' => 24, 'enter' => 'slide', 'duration' => 12]),
                self::text('right', 'Right', 'JavaScript', 580, 520, 440, 150, ['size' => 64, 'align' => 'center', 'valign' => 'middle', 'fill' => '#dc2626', 'radius' => 24, 'start' => 0.4, 'enter' => 'slide', 'duration' => 11.6]),
                self::arrow('divider', 'Divider', 540, 460, 540, 1500, ['stroke' => '#94a3b8', 'strokeWidth' => 6, 'head' => false, 'draw' => 1, 'duration' => 12]),
                self::text('left-note', 'Left note', 'Simple and readable', 60, 760, 440, 300, ['size' => 46, 'bold' => false, 'start' => 1.5, 'reveal' => 'type', 'duration' => 10.5]),
                self::text('right-note', 'Right note', 'Runs in every browser', 580, 760, 440, 300, ['size' => 46, 'bold' => false, 'start' => 2.5, 'reveal' => 'type', 'duration' => 9.5]),
            ],
            'tip' => self::tipsVideo('3 tips to remember', ['Write the simplest thing first', 'Name things clearly', 'Test as you go'])['objects'],
            default => [
                self::text('title', 'Title', 'Your title here', 90, 300, 900, 200, ['size' => 84, 'align' => 'center', 'duration' => 10, 'enter' => 'pop']),
            ],
        };

        return [
            'background' => '#0f172a',
            'background2' => '#1e293b',
            'duration' => 12,
            'objects' => $objects,
        ];
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private static function base(string $id, string $type, string $name, array $extra): array
    {
        return array_merge([
            'id' => $id, 'type' => $type, 'name' => $name,
            'start' => 0, 'duration' => 10,
            'x' => 0, 'y' => 0, 'scale' => 1, 'rotation' => 0, 'opacity' => 1,
            'enter' => 'none', 'exit' => 'none',
        ], $extra);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private static function text(string $id, string $name, string $text, int $x, int $y, int $w, int $h, array $extra = []): array
    {
        return self::base($id, 'text', $name, array_merge([
            'text' => $text, 'x' => $x, 'y' => $y, 'w' => $w, 'h' => $h,
            'size' => 64, 'color' => '#ffffff', 'bold' => true, 'align' => 'left',
        ], $extra));
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private static function code(string $id, string $name, string $code, int $x, int $y, int $w, int $h, array $extra = []): array
    {
        return self::base($id, 'code', $name, array_merge([
            'code' => $code, 'language' => 'python', 'theme' => 'vscode', 'x' => $x, 'y' => $y, 'w' => $w, 'h' => $h,
            'size' => 36, 'cps' => 20, 'typing' => true, 'cursor' => true,
        ], $extra));
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private static function rect(string $id, string $name, int $x, int $y, int $w, int $h, array $extra = []): array
    {
        return self::base($id, 'rect', $name, array_merge([
            'x' => $x, 'y' => $y, 'w' => $w, 'h' => $h, 'fill' => '#2563eb', 'strokeWidth' => 0, 'radius' => 24,
        ], $extra));
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private static function arrow(string $id, string $name, int $x, int $y, int $x2, int $y2, array $extra = []): array
    {
        return self::base($id, 'arrow', $name, array_merge([
            'x' => $x, 'y' => $y, 'x2' => $x2, 'y2' => $y2, 'stroke' => '#ffffff', 'strokeWidth' => 12, 'head' => true, 'draw' => 0.8,
        ], $extra));
    }

    /**
     * The project type a template starts as.
     */
    public static function type(string $template): ProjectType
    {
        return match ($template) {
            'code' => ProjectType::Code,
            'flow' => ProjectType::Explain,
            'compare' => ProjectType::Compare,
            'tip' => ProjectType::Tips,
            default => ProjectType::Other,
        };
    }

    /**
     * A title, then a code window that types itself, then the result.
     *
     * @return array<string, mixed>
     */
    public static function codeVideo(string $title, string $language, string $code, string $result, string $resultLabel = 'Output'): array
    {
        $lines = substr_count($code, "\n") + 1;
        $typingEnds = 0.8 + strlen($code) / 16;
        $resultAt = round($typingEnds + 0.8, 1);
        $duration = max(10, (int) ceil($resultAt + 4));
        $codeHeight = 150 + $lines * 57;
        $labelTop = 460 + $codeHeight + 70;

        return self::wrap($duration, [
            self::text('title', 'Title', $title, 90, 150, 900, 230, ['size' => 70, 'align' => 'center', 'valign' => 'middle', 'enter' => 'slide', 'duration' => $duration]),
            self::code('code', 'Code', $code, 70, 460, 940, $codeHeight, ['language' => $language, 'start' => 0.6, 'duration' => $duration - 0.6, 'size' => 38, 'cps' => 16, 'enter' => 'pop']),
            self::text('label', 'Result label', $resultLabel, 90, $labelTop, 900, 80, ['size' => 40, 'align' => 'center', 'color' => '#94a3b8', 'bold' => false, 'start' => $resultAt, 'duration' => $duration - $resultAt, 'enter' => 'fade']),
            self::text('result', 'Result', $result, 70, $labelTop + 90, 940, 260, ['size' => 84, 'align' => 'center', 'valign' => 'middle', 'color' => '#4ade80', 'font' => 'mono', 'start' => $resultAt, 'duration' => $duration - $resultAt, 'enter' => 'pop']),
        ]);
    }

    /**
     * Two sides, A against B, with a few typed-out points each.
     *
     * @param  list<string>  $leftPoints
     * @param  list<string>  $rightPoints
     * @return array<string, mixed>
     */
    public static function compareVideo(string $title, string $left, string $right, array $leftPoints, array $rightPoints): array
    {
        return self::wrap(14, [
            self::text('title', 'Title', $title, 90, 150, 900, 230, ['size' => 70, 'align' => 'center', 'valign' => 'middle', 'duration' => 14]),
            self::text('left', 'Left', $left, 50, 470, 460, 150, ['size' => 54, 'align' => 'center', 'valign' => 'middle', 'fill' => '#2563eb', 'radius' => 28, 'enter' => 'slide', 'duration' => 14]),
            self::text('right', 'Right', $right, 570, 470, 460, 150, ['size' => 54, 'align' => 'center', 'valign' => 'middle', 'fill' => '#dc2626', 'radius' => 28, 'start' => 0.4, 'enter' => 'slide', 'duration' => 13.6]),
            self::arrow('divider', 'Divider', 540, 440, 540, 1560, ['stroke' => '#475569', 'strokeWidth' => 6, 'head' => false, 'draw' => 1.2, 'duration' => 14]),
            self::text('left-points', 'Left points', implode("\n\n", $leftPoints), 50, 700, 460, 760, ['size' => 40, 'bold' => false, 'start' => 1.6, 'reveal' => 'type', 'cps' => 16, 'duration' => 12.4]),
            self::text('right-points', 'Right points', implode("\n\n", $rightPoints), 570, 700, 460, 760, ['size' => 40, 'bold' => false, 'start' => 2.4, 'reveal' => 'type', 'cps' => 16, 'duration' => 11.6]),
        ]);
    }

    /**
     * A labelled box slides into a target box, then a note types out underneath.
     *
     * @return array<string, mixed>
     */
    public static function flowVideo(string $title, string $item, string $target, string $note): array
    {
        return self::wrap(12, [
            self::text('title', 'Title', $title, 90, 150, 900, 230, ['size' => 66, 'align' => 'center', 'valign' => 'middle', 'duration' => 12]),
            self::rect('target', $target, 620, 640, 360, 360, ['fill' => '#1e293b', 'stroke' => '#38bdf8', 'strokeWidth' => 8, 'radius' => 40, 'duration' => 12]),
            self::text('target-label', $target.' label', $target, 620, 1030, 360, 90, ['size' => 44, 'align' => 'center', 'color' => '#38bdf8', 'duration' => 12]),
            self::text('item', $item, $item, 100, 740, 330, 160, [
                'size' => 50, 'align' => 'center', 'valign' => 'middle', 'fill' => '#2563eb', 'radius' => 32, 'duration' => 12, 'enter' => 'pop',
                'tracks' => [
                    'x' => [['t' => 1.5, 'v' => 100, 'ease' => 'ease-in-out'], ['t' => 3.5, 'v' => 635]],
                    'y' => [['t' => 1.5, 'v' => 740, 'ease' => 'ease-in-out'], ['t' => 3.5, 'v' => 740]],
                ],
            ]),
            self::arrow('arrow', 'Arrow', 540, 1380, 540, 1180, ['stroke' => '#facc15', 'start' => 4, 'duration' => 8]),
            self::text('note', 'Note', $note, 90, 1420, 900, 330, ['size' => 46, 'align' => 'center', 'bold' => false, 'start' => 4.8, 'reveal' => 'type', 'cps' => 18, 'duration' => 7.2]),
        ]);
    }

    /**
     * A title and a short list of points that slide in one after another.
     *
     * @param  list<string>  $points
     * @return array<string, mixed>
     */
    public static function tipsVideo(string $title, array $points): array
    {
        $points = array_slice($points, 0, 6);
        $duration = (int) ceil(1 + count($points) * 1.5 + 3);
        $colors = ['#2563eb', '#7c3aed', '#db2777', '#ea580c', '#059669', '#0891b2'];
        $objects = [self::text('title', 'Title', $title, 90, 130, 900, 230, ['size' => 68, 'align' => 'center', 'valign' => 'middle', 'duration' => $duration])];

        foreach ($points as $index => $point) {
            $appears = round(1 + $index * 1.5, 1);
            $objects[] = self::text("point-$index", 'Point '.($index + 1), ($index + 1).'.  '.$point, 70, 430 + $index * 215, 940, 185, [
                'size' => 42, 'valign' => 'middle', 'fill' => $colors[$index % count($colors)], 'radius' => 32, 'padding' => 36,
                'start' => $appears, 'duration' => $duration - $appears, 'enter' => 'slide',
            ]);
        }

        return self::wrap($duration, $objects);
    }

    /**
     * A title, a caption and several pictures that fly in from the left and settle into a grid.
     *
     * @param  list<array{asset: string, label: string}>  $pictures
     * @return array<string, mixed>
     */
    public static function picturesVideo(string $title, string $caption, array $pictures): array
    {
        $pictures = array_slice($pictures, 0, 4);
        $size = 360;
        $duration = (int) ceil(2 + count($pictures) * 1.6 + 5);
        $objects = [self::text('title', 'Title', $title, 90, 130, 900, 230, ['size' => 68, 'align' => 'center', 'valign' => 'middle', 'duration' => $duration])];

        foreach ($pictures as $index => $picture) {
            $x = 130 + ($index % 2) * 460;
            $y = 440 + intdiv($index, 2) * 500;
            $at = round(1 + $index * 1.6, 1);

            $objects[] = self::base("picture-$index", 'image', $picture['label'], [
                'asset' => $picture['asset'], 'x' => $x, 'y' => $y, 'w' => $size, 'h' => $size, 'ratio' => 1, 'lock' => true,
                'start' => 0, 'duration' => $duration,
                'tracks' => [
                    'x' => [['t' => $at, 'v' => -420, 'ease' => 'back'], ['t' => $at + 1, 'v' => $x]],
                    'y' => [['t' => $at, 'v' => $y, 'ease' => 'back'], ['t' => $at + 1, 'v' => $y]],
                ],
            ]);
            $objects[] = self::text("name-$index", $picture['label'].' name', $picture['label'], $x - 20, $y + $size + 10, $size + 40, 80, [
                'size' => 44, 'align' => 'center', 'start' => $at + 1, 'duration' => $duration - $at - 1, 'enter' => 'fade',
            ]);
        }

        $objects[] = self::text('caption', 'Caption', $caption, 90, 1500, 900, 300, [
            'size' => 48, 'align' => 'center', 'bold' => false, 'color' => '#cbd5e1', 'start' => $duration - 4.5, 'duration' => 4.5, 'reveal' => 'type', 'cps' => 22,
        ]);

        return self::wrap($duration, $objects);
    }

    /**
     * @param  list<array<string, mixed>>  $objects
     * @return array<string, mixed>
     */
    private static function wrap(int|float $duration, array $objects): array
    {
        return ['background' => '#0f172a', 'background2' => '#1e293b', 'duration' => $duration, 'objects' => $objects];
    }
}
