<?php

namespace Database\Seeders\Showcase;

/**
 * A small helper for scripting a studio video by absolute time, the way you would in an editor:
 * place things, fly them between points, pulse them, shake them, and say lines of voice-over.
 *
 * Every time passed in is seconds from the start of the video. The helper turns them into the
 * per-object keyframes the editor and the renderer use.
 */
final class Timeline
{
    /** @var list<array<string, mixed>> */
    private array $objects = [];

    private int $counter = 0;

    public function __construct(
        private readonly float $duration,
        private readonly string $top = '#0b1020',
        private readonly string $bottom = '#1b2550',
        private readonly bool $music = true,
    ) {}

    /**
     * @param  array<string, mixed>  $props
     */
    public function add(string $type, string $name, float $start, float $length, array $props = []): string
    {
        $id = 'o'.(++$this->counter);

        $this->objects[] = array_merge([
            'id' => $id, 'type' => $type, 'name' => $name,
            'start' => round($start, 2), 'duration' => round(max(0.1, $length), 2),
            'x' => 0, 'y' => 0, 'scale' => 1, 'rotation' => 0, 'opacity' => 1,
            'enter' => 'none', 'exit' => 'none',
        ], $props);

        return $id;
    }

    /**
     * @param  array<string, mixed>  $props
     */
    public function text(string $name, string $text, int $x, int $y, int $w, int $h, float $start, float $length, array $props = []): string
    {
        return $this->add('text', $name, $start, $length, array_merge([
            'text' => $text, 'x' => $x, 'y' => $y, 'w' => $w, 'h' => $h,
            'size' => 56, 'color' => '#ffffff', 'bold' => true, 'align' => 'center', 'valign' => 'middle',
        ], $props));
    }

    /**
     * @param  array<string, mixed>  $props
     */
    public function box(string $name, int $x, int $y, int $w, int $h, float $start, float $length, array $props = []): string
    {
        return $this->add('rect', $name, $start, $length, array_merge([
            'x' => $x, 'y' => $y, 'w' => $w, 'h' => $h, 'fill' => '#2563eb', 'strokeWidth' => 0, 'radius' => 24,
        ], $props));
    }

    /**
     * @param  array<string, mixed>  $props
     */
    public function circle(string $name, int $x, int $y, int $w, int $h, float $start, float $length, array $props = []): string
    {
        return $this->add('ellipse', $name, $start, $length, array_merge([
            'x' => $x, 'y' => $y, 'w' => $w, 'h' => $h, 'fill' => '#ef4444', 'strokeWidth' => 0,
        ], $props));
    }

    /**
     * @param  array<string, mixed>  $props
     */
    public function arrow(string $name, int $x, int $y, int $x2, int $y2, float $start, float $length, array $props = []): string
    {
        return $this->add('arrow', $name, $start, $length, array_merge([
            'x' => $x, 'y' => $y, 'x2' => $x2, 'y2' => $y2, 'stroke' => '#ffffff', 'strokeWidth' => 12, 'head' => true, 'draw' => 0.8,
        ], $props));
    }

    /**
     * @param  array<string, mixed>  $props
     */
    public function code(string $name, string $code, string $language, int $x, int $y, int $w, int $h, float $start, float $length, array $props = []): string
    {
        return $this->add('code', $name, $start, $length, array_merge([
            'code' => $code, 'language' => $language, 'theme' => 'vscode', 'x' => $x, 'y' => $y, 'w' => $w, 'h' => $h,
            'size' => 38, 'cps' => 16, 'typing' => true, 'cursor' => true,
        ], $props));
    }

    /**
     * A line of voice-over. The length is only a guess used for the timeline bar.
     */
    public function say(float $at, string $text): string
    {
        $words = str_word_count($text);

        return $this->add('voice', 'Voice: '.mb_strimwidth($text, 0, 30, '...'), $at, max(1.0, round($words / 2.6, 1)), ['text' => $text]);
    }

    /**
     * Keyframes for one property. Each key is [time in the video, value, optional easing].
     * The easing belongs to the move that starts at that key.
     *
     * @param  list<array{0: float|int, 1: float|int, 2?: string}>  $keys
     */
    public function key(string $id, string $prop, array $keys): void
    {
        foreach ($this->objects as &$object) {
            if ($object['id'] !== $id) {
                continue;
            }

            foreach ($keys as $key) {
                $object['tracks'][$prop][] = array_filter([
                    't' => round(max(0.0, $key[0] - $object['start']), 2),
                    'v' => $key[1],
                    'ease' => $key[2] ?? null,
                ], fn ($value) => $value !== null);
            }

            usort($object['tracks'][$prop], fn ($a, $b) => $a['t'] <=> $b['t']);

            return;
        }
    }

    /**
     * Move an object from one point to another between two times (a simple start and end movement).
     */
    public function fly(string $id, int $fromX, int $fromY, int $toX, int $toY, float $from, float $to, string $ease = 'ease-in-out'): void
    {
        $this->key($id, 'x', [[$from, $fromX, $ease], [$to, $toX]]);
        $this->key($id, 'y', [[$from, $fromY, $ease], [$to, $toY]]);
    }

    /**
     * Grow and shrink back, like a heartbeat.
     */
    public function pulse(string $id, float $at, float $by = 1.15, float $length = 0.5): void
    {
        $this->key($id, 'scale', [[$at, 1, 'ease-out'], [$at + $length / 2, $by, 'ease-in'], [$at + $length, 1]]);
    }

    /**
     * A quick left-right wobble.
     */
    public function shake(string $id, float $at, float $degrees = 4, float $length = 0.7): void
    {
        $step = $length / 5;
        $this->key($id, 'rotation', [
            [$at, 0, 'ease-in-out'], [$at + $step, -$degrees, 'ease-in-out'], [$at + 2 * $step, $degrees, 'ease-in-out'],
            [$at + 3 * $step, -$degrees / 2, 'ease-in-out'], [$at + 4 * $step, $degrees / 2, 'ease-in-out'], [$at + 5 * $step, 0],
        ]);
    }

    /**
     * Blink an object's opacity between full and dim, a number of times.
     */
    public function blink(string $id, float $at, int $times = 3, float $every = 0.5): void
    {
        $keys = [];
        for ($i = 0; $i < $times; $i++) {
            $keys[] = [$at + $i * $every * 2, 1, 'ease-in-out'];
            $keys[] = [$at + $i * $every * 2 + $every, 0.25, 'ease-in-out'];
        }
        $keys[] = [$at + $times * $every * 2, 1];

        $this->key($id, 'opacity', $keys);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'background' => $this->top,
            'background2' => $this->bottom,
            'music' => $this->music,
            'duration' => $this->duration,
            'objects' => $this->objects,
        ];
    }
}
