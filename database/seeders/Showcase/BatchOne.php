<?php

namespace Database\Seeders\Showcase;

use App\Enums\ProjectType;

/**
 * Batch 1: five longer, hand-scripted videos (about 40 seconds each) with voice-over, music,
 * moving pieces and keyframed motion. Each one tells a small story instead of filling a template.
 */
final class BatchOne
{
    /**
     * @return list<array{name: string, type: ProjectType, category: string, language?: string, tags: list<string>, document: array<string, mixed>}>
     */
    public static function projects(): array
    {
        return [
            [
                'name' => 'What happens when you type google.com',
                'type' => ProjectType::Explain, 'category' => 'Web and CSS', 'tags' => ['browser', 'dns', 'http', 'networking'],
                'document' => self::googleJourney(),
            ],
            [
                'name' => 'Big O: the algorithm race',
                'type' => ProjectType::Explain, 'category' => 'Algorithms', 'tags' => ['big-o', 'performance', 'interview'],
                'document' => self::bigORace(),
            ],
            [
                'name' => 'Spot the bug in 8 seconds',
                'type' => ProjectType::Code, 'category' => 'Interview prep', 'language' => 'python', 'tags' => ['bugs', 'interview', 'challenge'],
                'document' => self::bugHunt(),
            ],
            [
                'name' => '0.1 + 0.2 is not 0.3',
                'type' => ProjectType::Explain, 'category' => 'Computer science', 'language' => 'python', 'tags' => ['floating-point', 'gotcha', 'binary'],
                'document' => self::floatSurprise(),
            ],
            [
                'name' => 'The stack, explained with plates',
                'type' => ProjectType::Explain, 'category' => 'Data structures', 'language' => 'python', 'tags' => ['stack', 'lifo', 'data-structures', 'beginner'],
                'document' => self::stackOfPlates(),
            ],
        ];
    }

    /**
     * Packets travel between the browser, a DNS server and the website's server.
     *
     * @return array<string, mixed>
     */
    private static function googleJourney(): array
    {
        $t = new Timeline(41, '#071226', '#10264d');

        $t->text('Title', 'What happens when you type google.com?', 70, 90, 940, 230, 0.2, 40.8, ['size' => 62, 'enter' => 'slide']);
        $t->say(0.5, 'You type google dot com and press enter. In less than a second, a lot happens.');

        $t->box('Address bar', 90, 360, 900, 120, 1.2, 39.8, ['fill' => '#0f1b33', 'stroke' => '#38bdf8', 'strokeWidth' => 6, 'radius' => 60, 'enter' => 'pop']);
        $t->text('Address', 'google.com', 150, 372, 600, 96, 2.0, 39, ['size' => 52, 'font' => 'mono', 'align' => 'left', 'color' => '#e2e8f0', 'reveal' => 'type', 'cps' => 7]);
        $enter = $t->text('Enter key', 'ENTER', 770, 390, 190, 60, 4.2, 36.8, ['size' => 30, 'fill' => '#38bdf8', 'color' => '#06223a', 'radius' => 20, 'enter' => 'pop']);
        $t->pulse($enter, 4.6, 1.25, 0.6);

        $browser = $t->text('Browser', 'Your browser', 370, 560, 340, 170, 5.0, 36, ['size' => 44, 'fill' => '#2563eb', 'radius' => 36, 'enter' => 'pop']);
        $t->pulse($browser, 27.3, 1.1, 0.8);

        // Step 1: ask DNS where the site lives.
        $t->say(6.5, 'First, your browser asks a D N S server: where is google dot com?');
        $t->text('DNS server', 'DNS server', 370, 950, 340, 170, 6.5, 10.7, ['size' => 44, 'fill' => '#7c3aed', 'radius' => 36, 'enter' => 'pop', 'exit' => 'fade']);
        $t->arrow('Ask DNS', 470, 740, 470, 945, 7.0, 10.2, ['stroke' => '#facc15', 'strokeWidth' => 10]);
        $t->text('Ask label', 'Where is google.com?', 30, 800, 410, 110, 7.4, 9.8, ['size' => 32, 'align' => 'right', 'color' => '#facc15', 'reveal' => 'type', 'cps' => 22]);
        $ask = $t->box('Question packet', 448, 740, 44, 44, 7.3, 1.7, ['fill' => '#facc15', 'radius' => 12, 'exit' => 'fade']);
        $t->fly($ask, 448, 745, 448, 905, 7.4, 8.6);

        $t->say(11.4, 'The D N S server replies with an I P address, like a street number for the website.');
        $t->arrow('DNS answer', 610, 945, 610, 740, 11.0, 6.2, ['stroke' => '#4ade80', 'strokeWidth' => 10]);
        $answer = $t->box('Answer packet', 588, 905, 44, 44, 11.4, 1.7, ['fill' => '#4ade80', 'radius' => 12, 'exit' => 'fade']);
        $t->fly($answer, 588, 905, 588, 745, 11.5, 12.7);
        $t->text('Answer label', '142.250.74.14', 650, 810, 400, 90, 12.7, 4.5, ['size' => 36, 'font' => 'mono', 'align' => 'left', 'color' => '#4ade80', 'reveal' => 'type', 'cps' => 14]);

        // Step 2: ask the website's server for the page.
        $t->say(17.5, 'Now your browser connects to that server and asks for the page.');
        $t->text('Web server', 'google.com server', 370, 950, 340, 170, 17.5, 13.5, ['size' => 38, 'fill' => '#059669', 'radius' => 36, 'enter' => 'pop']);
        $t->arrow('Ask server', 470, 740, 470, 945, 18.3, 12.7, ['stroke' => '#facc15', 'strokeWidth' => 10]);
        $t->text('Ask server label', 'GET /', 30, 810, 410, 90, 18.7, 12.3, ['size' => 40, 'font' => 'mono', 'align' => 'right', 'color' => '#facc15', 'reveal' => 'type', 'cps' => 6]);
        $get = $t->box('GET packet', 448, 740, 44, 44, 18.6, 1.7, ['fill' => '#facc15', 'radius' => 12, 'exit' => 'fade']);
        $t->fly($get, 448, 745, 448, 905, 18.7, 19.9);

        $t->say(22.5, 'The server sends back H T M L, C S S, and JavaScript.');
        $t->arrow('Page answer', 610, 945, 610, 740, 23.0, 8, ['stroke' => '#4ade80', 'strokeWidth' => 10]);
        $t->text('Page label', 'HTML, CSS, JavaScript', 650, 800, 400, 120, 23.4, 7.6, ['size' => 32, 'align' => 'left', 'color' => '#4ade80', 'reveal' => 'type', 'cps' => 18]);
        foreach ([['HTML', 385, '#f97316', '#ffffff', 23.6], ['CSS', 495, '#38bdf8', '#06223a', 24.2], ['JS', 605, '#facc15', '#1a1a1a', 24.8]] as [$label, $x, $fill, $ink, $at]) {
            $chip = $t->text($label.' file', $label, $x, 930, 100, 56, $at, 1.9, ['size' => 26, 'fill' => $fill, 'color' => $ink, 'radius' => 16, 'exit' => 'fade']);
            $t->fly($chip, $x, 930, $x, 742, $at, $at + 1.4);
        }

        // Step 3: the browser builds the page.
        $t->say(27.3, 'Your browser puts the pieces together, and draws the page.');
        $t->box('Web page', 270, 1200, 540, 330, 28.0, 13, ['fill' => '#f8fafc', 'radius' => 28, 'enter' => 'pop']);
        $t->text('Page header', 'google.com', 270, 1200, 540, 64, 28.2, 12.8, ['size' => 32, 'fill' => '#2563eb', 'radius' => 28]);
        $t->box('Page header square', 270, 1236, 540, 28, 28.2, 12.8, ['fill' => '#2563eb', 'radius' => 0]);
        $t->text('Page heading', 'Hello!', 270, 1290, 540, 120, 29.0, 12, ['size' => 72, 'color' => '#0f172a', 'enter' => 'pop']);
        $t->box('Search box', 340, 1420, 400, 70, 29.6, 11.4, ['fill' => '#ffffff', 'stroke' => '#94a3b8', 'strokeWidth' => 4, 'radius' => 35, 'enter' => 'fade']);
        $t->text('Search hint', 'Search...', 380, 1425, 320, 60, 29.8, 11.2, ['size' => 30, 'color' => '#94a3b8', 'bold' => false, 'align' => 'left']);

        $t->say(32.0, 'All of that, in less than a second.');
        $counter = $t->text('Timer', '0.4 seconds', 90, 1560, 900, 110, 32.5, 8.5, ['size' => 64, 'color' => '#facc15', 'enter' => 'pop']);
        $t->pulse($counter, 33.5, 1.12, 0.8);

        $t->say(35.0, 'Tiny steps. Huge speed. Follow for more.');
        $t->text('Follow', 'Follow for more web secrets', 190, 1670, 700, 90, 37.0, 4, ['size' => 38, 'fill' => '#2563eb', 'radius' => 45, 'enter' => 'pop']);

        return $t->toArray();
    }

    /**
     * Four runners, one per complexity class, racing as the list grows.
     *
     * @return array<string, mixed>
     */
    private static function bigORace(): array
    {
        $t = new Timeline(45, '#14091f', '#3a1456');
        $lanes = [
            ['O(1)', 'Array index', '#22c55e'],
            ['O(log n)', 'Binary search', '#38bdf8'],
            ['O(n)', 'Loop once', '#facc15'],
            ['O(n²)', 'Nested loops', '#f43f5e'],
        ];

        $t->text('Title', 'Big O: the algorithm race', 70, 90, 940, 150, 0.2, 44.8, ['size' => 66, 'enter' => 'slide']);
        $t->text('Subtitle', 'Same job. A list that keeps growing.', 70, 250, 940, 70, 0.8, 44, ['size' => 34, 'color' => '#c4b5fd', 'bold' => false, 'enter' => 'fade']);
        $t->say(0.5, 'Four algorithms enter a race. Same job, but the list keeps growing.');

        foreach ($lanes as $k => [$big, $what, $color]) {
            $y = 560 + $k * 200;
            $t->text("Lane $big", "$big  $what", 70, $y, 640, 64, 1.5 + $k * 0.2, 43, ['size' => 38, 'color' => $color, 'align' => 'left', 'enter' => 'slide']);
            $t->box("Track $big", 70, $y + 104, 940, 12, 1.5 + $k * 0.2, 43, ['fill' => '#3b2a57', 'radius' => 6]);
        }
        $t->box('Finish line', 1004, 540, 8, 800, 2.0, 43, ['fill' => '#ffffff', 'opacity' => 0.4, 'radius' => 4]);
        $t->text('Finish label', 'FINISH', 810, 490, 200, 44, 2.0, 43, ['size' => 26, 'color' => '#a78bfa', 'align' => 'right']);

        // Each phase: [start, end, headline, durations, how far each runner gets, step counts]
        $phases = [
            [7.0, 12.0, 'n = 10', [0.9, 1.1, 1.5, 2.0], [1, 1, 1, 1], ['1 step', '3 steps', '10 steps', '100 steps'], 80],
            [13.0, 23.0, 'n = 1,000', [0.9, 1.2, 4.5, 9.0], [1, 1, 1, 0.12], ['1 step', '10 steps', '1,000 steps', '1,000,000 steps'], 80],
            [24.5, 37.0, 'n = 1,000,000', [0.9, 1.3, 8.0, 12.0], [1, 1, 1, 0.015], ['1 step', '20 steps', '1,000,000 steps', '1 trillion steps'], 74],
        ];

        foreach ($phases as [$from, $to, $headline, $durations, $reach, $steps, $size]) {
            $t->text("Count $headline", $headline, 90, 1480, 900, 130, $from - 0.8, $to - $from + 0.8, ['size' => $size, 'color' => '#facc15', 'enter' => 'pop']);

            foreach ($lanes as $k => [$big, , $color]) {
                $y = 560 + $k * 200;
                $arrives = $from + $durations[$k];
                $endX = (int) (70 + 860 * $reach[$k]);

                $runner = $t->box("Runner $big", 70, $y + 72, 76, 76, $from, $to - $from, ['fill' => $color, 'radius' => 22, 'exit' => 'fade', 'enter' => 'pop']);
                $t->key($runner, 'x', [[$from, 70, 'ease-in-out'], [$arrives, $endX]]);
                $t->key($runner, 'rotation', [[$from, 0, 'linear'], [$arrives, (int) ($durations[$k] * 300)]]);

                $t->text("Steps $big", $steps[$k], 600, $y + 8, 410, 56, min($arrives, $from + 5), $to - min($arrives, $from + 5), ['size' => 32, 'align' => 'right', 'enter' => 'fade']);
            }
        }

        $t->say(6.2, 'First, ten items. Everyone finishes in a blink.');
        $t->say(12.0, 'Now a thousand items.');
        $t->say(15.0, 'Watch O of n squared fall behind.');
        $t->say(19.6, 'O of one and O of log n barely notice.');
        $t->say(24.0, 'Now one million items.');
        $t->say(27.0, 'O of n squared would take days. O of log n needs twenty steps.');
        $t->say(33.6, 'That is why Big O matters. Pick your algorithm wisely.');

        $t->box('Verdict card', 70, 1400, 940, 260, 37.5, 7.5, ['fill' => '#1e1233', 'stroke' => '#7c3aed', 'strokeWidth' => 4, 'radius' => 32, 'enter' => 'pop']);
        $t->text('Verdict', "Fast: O(1), O(log n)\nFine: O(n)\nDanger: O(n²)", 110, 1420, 860, 220, 37.9, 7.1, ['size' => 42, 'align' => 'left', 'reveal' => 'type', 'cps' => 16]);

        return $t->toArray();
    }

    /**
     * Two rounds of "find the bug" with a ticking timer, a magnifier and the answer.
     *
     * @return array<string, mixed>
     */
    private static function bugHunt(): array
    {
        $t = new Timeline(48, '#1a0f14', '#3b1d2b');

        $t->text('Title', 'Spot the bug', 70, 90, 940, 150, 0.2, 47.8, ['size' => 80, 'enter' => 'slide']);

        $t->say(0.5, 'Can you spot the bug? You have eight seconds.');
        self::bugRound($t, [
            'start' => 1.0, 'end' => 23.5, 'chip' => 'Round 1 · Python', 'chipColor' => '#7c3aed', 'language' => 'python',
            'code' => "items = [\"a\", \"b\", \"c\"]\nfor i in range(1, len(items)):\n    print(items[i])",
            'lines' => 3, 'highlight' => 1, 'expected' => 'Expected output: a b c', 'count' => 8, 'reveal' => 14.5,
            'explain' => 'range(1, ...) starts at 1, so the first item "a" is skipped.', 'fix' => 'Fix: for i in range(len(items)):',
        ]);
        $t->say(14.5, 'Here it is. Range starts at one, so the first item is skipped.');
        $t->say(19.8, 'Start at zero, or loop over the list directly.');

        $t->say(24.5, 'Round two. Easy, right?');
        self::bugRound($t, [
            'start' => 24.0, 'end' => 42.2, 'chip' => 'Round 2 · JavaScript', 'chipColor' => '#ca8a04', 'language' => 'javascript',
            'code' => "let score = 3;\nif (score = 5) {\n  console.log(\"five!\");\n}",
            'lines' => 4, 'highlight' => 1, 'expected' => 'Expected: nothing printed, score is 3', 'count' => 5, 'reveal' => 34.5,
            'explain' => 'One equals sign assigns a value, so the condition is always true.', 'fix' => 'Fix: if (score === 5) {',
        ]);
        $t->say(34.5, 'A single equals sign assigns a value, so the condition is always true.');
        $t->say(39.5, 'Use triple equals to compare.');

        $t->text('Challenge', 'Comment your answer before the reveal!', 70, 880, 940, 220, 42.4, 5.6, ['size' => 56, 'color' => '#facc15', 'enter' => 'pop']);
        $t->text('Daily', 'A new bug every day', 190, 1160, 700, 90, 43.4, 4.6, ['size' => 40, 'fill' => '#7c3aed', 'radius' => 45, 'enter' => 'pop']);
        $t->say(42.4, 'Comment your answer before the reveal. A new bug every day.');

        return $t->toArray();
    }

    /**
     * @param  array{start: float, end: float, chip: string, chipColor: string, language: string, code: string, lines: int, highlight: int, expected: string, count: int, reveal: float, explain: string, fix: string}  $round
     */
    private static function bugRound(Timeline $t, array $round): void
    {
        ['start' => $from, 'end' => $to, 'reveal' => $reveal, 'count' => $count] = $round;
        $length = $to - $from;
        $countFrom = $reveal - $count;
        $codeTop = 430;
        $lineTop = $codeTop + 92 + 66 * $round['highlight'];
        $bottom = $codeTop + 150 + 66 * $round['lines'];

        $t->text("Chip {$round['chip']}", $round['chip'], 300, 270, 480, 64, $from, $length, ['size' => 32, 'fill' => $round['chipColor'], 'radius' => 32, 'enter' => 'pop']);
        $t->code("Code {$round['chip']}", $round['code'], $round['language'], 70, $codeTop, 940, 150 + $round['lines'] * 66, $from + 0.6, $length - 0.6, ['cps' => 22, 'size' => 44, 'enter' => 'pop']);
        $t->text("Expected {$round['chip']}", $round['expected'], 70, $bottom + 30, 940, 80, $countFrom - 2.5, $length - ($countFrom - 2.5 - $from), ['size' => 38, 'color' => '#4ade80', 'enter' => 'fade']);

        $t->text("Find it {$round['chip']}", 'Find the bug!', 70, $bottom + 120, 940, 110, $countFrom - 1.0, $count + 1.0, ['size' => 70, 'color' => '#facc15', 'enter' => 'pop']);
        for ($i = 0; $i < $count; $i++) {
            $heat = $count > 1 ? $i / ($count - 1) : 1;
            $number = $t->text("Count {$round['chip']} ".($count - $i), (string) ($count - $i), 390, $bottom + 250, 300, 240, $countFrom + $i, 1.0, [
                'size' => 210, 'color' => $heat < 0.5 ? '#4ade80' : ($heat < 0.8 ? '#fbbf24' : '#f87171'), 'enter' => 'pop',
            ]);
            $t->pulse($number, $countFrom + $i + 0.05, 1.08, 0.5);
        }
        $t->box("Timer back {$round['chip']}", 90, 1400, 900, 30, $countFrom, $count, ['fill' => '#2a1a1f', 'radius' => 15]);
        $bar = $t->box("Timer {$round['chip']}", 90, 1400, 900, 30, $countFrom, $count, ['fill' => '#f59e0b', 'radius' => 15]);
        $t->key($bar, 'scale', [[$countFrom, 1, 'linear'], [$reveal, 0.02]]);

        $highlight = $t->box("Bug {$round['chip']}", 105, $lineTop - 8, 840, 66, $reveal, $to - $reveal, ['fill' => null, 'stroke' => '#facc15', 'strokeWidth' => 6, 'radius' => 14, 'enter' => 'pop']);
        $t->blink($highlight, $reveal + 0.8, 3, 0.35);

        $glass = $t->circle("Magnifier {$round['chip']}", 0, 0, 110, 110, $reveal, 3.2, ['fill' => null, 'stroke' => '#38bdf8', 'strokeWidth' => 10, 'exit' => 'fade']);
        $t->fly($glass, 900, 800, 700, $lineTop - 27, $reveal, $reveal + 1.2, 'back');
        $t->pulse($glass, $reveal + 1.3, 1.25, 0.8);

        $t->text("Why {$round['chip']}", $round['explain'], 70, $bottom + 120, 940, 260, $reveal + 0.8, $to - $reveal - 0.8, ['size' => 44, 'color' => '#e2e8f0', 'bold' => false, 'reveal' => 'type', 'cps' => 24]);
        $t->text("Fix {$round['chip']}", $round['fix'], 110, $bottom + 420, 860, 110, $reveal + 3.2, $to - $reveal - 3.2, ['size' => 36, 'font' => 'mono', 'fill' => '#052e1b', 'color' => '#4ade80', 'padding' => 30, 'radius' => 24, 'align' => 'left', 'enter' => 'pop']);
    }

    /**
     * The famous floating point surprise, then why it happens and how to avoid it.
     *
     * @return array<string, mixed>
     */
    private static function floatSurprise(): array
    {
        $t = new Timeline(46, '#07201c', '#0f3d3a');

        $t->text('Title', '0.1 + 0.2 is not 0.3', 70, 80, 940, 240, 0.2, 45.8, ['size' => 78, 'enter' => 'slide']);

        // Scene 1: the surprise, in several languages.
        $t->say(0.6, 'Add point one and point two, in any language.');
        $t->text('Python chip', 'Python', 70, 380, 220, 60, 1.0, 16, ['size' => 32, 'fill' => '#3776ab', 'color' => '#ffd343', 'radius' => 30, 'enter' => 'pop']);
        $t->code('Python code', 'print(0.1 + 0.2)', 'python', 70, 450, 940, 225, 1.2, 15.8, ['cps' => 10, 'size' => 50, 'enter' => 'pop']);
        $t->say(4.4, 'You expect point three. Python says this.');
        $out = $t->text('Python output', '0.30000000000000004', 40, 700, 1000, 110, 6.4, 10.6, ['size' => 58, 'font' => 'mono', 'color' => '#f87171', 'enter' => 'pop']);
        $t->shake($out, 6.9, 3, 0.8);
        $t->text('Stamp', 'WAIT, WHAT?', 290, 830, 500, 110, 7.4, 9.6, ['size' => 54, 'fill' => '#dc2626', 'radius' => 24, 'rotation' => -8, 'enter' => 'pop']);

        $t->say(9.2, 'JavaScript does exactly the same.');
        $t->text('JS chip', 'JavaScript', 70, 980, 300, 60, 9.0, 8, ['size' => 32, 'fill' => '#f7df1e', 'color' => '#1a1a1a', 'radius' => 30, 'enter' => 'pop']);
        $t->code('JS code', 'console.log(0.1 + 0.2)', 'javascript', 70, 1050, 940, 225, 9.4, 7.6, ['cps' => 12, 'size' => 50, 'enter' => 'pop']);
        $js = $t->text('JS output', '0.30000000000000004', 40, 1300, 1000, 110, 11.9, 5.1, ['size' => 58, 'font' => 'mono', 'color' => '#f87171', 'enter' => 'pop']);
        $t->shake($js, 12.3, 3, 0.8);

        $t->say(12.8, 'So do Java, PHP, Go, and C sharp.');
        foreach ([['Java', 70, '#e76f00', 13.4], ['PHP', 330, '#777bb4', 13.8], ['Go', 590, '#00add8', 14.2], ['C#', 850, '#68217a', 14.6]] as [$label, $x, $fill, $at]) {
            $t->text("$label chip", $label, $x, 1450, 160, 70, $at, 17 - $at, ['size' => 34, 'fill' => $fill, 'radius' => 24, 'enter' => 'pop']);
        }
        $t->text('Every language', 'Every language does this', 70, 1545, 940, 80, 15.0, 2.0, ['size' => 44, 'color' => '#99f6e4', 'enter' => 'fade', 'exit' => 'fade']);

        // Scene 2: why it happens.
        $t->say(17.3, 'Why? Computers count in binary.');
        $t->say(21.8, 'Some decimals, like point one, cannot be stored exactly.');
        $t->say(26.6, 'Like one third in decimal, it never ends.');
        $t->box('Decimal card', 70, 470, 940, 250, 17.5, 13.5, ['fill' => '#0f2f2c', 'stroke' => '#2dd4bf', 'strokeWidth' => 4, 'radius' => 32, 'enter' => 'pop']);
        $t->text('Decimal label', '1 ÷ 3 in decimal', 110, 490, 860, 60, 17.8, 13.2, ['size' => 34, 'color' => '#99f6e4', 'bold' => false]);
        $t->text('Decimal digits', '0.3333333333333...', 90, 560, 900, 130, 18.2, 12.8, ['size' => 54, 'font' => 'mono', 'color' => '#facc15', 'reveal' => 'type', 'cps' => 12]);
        $t->box('Binary card', 70, 780, 940, 250, 21.0, 10, ['fill' => '#0f2f2c', 'stroke' => '#fb7185', 'strokeWidth' => 4, 'radius' => 32, 'enter' => 'pop']);
        $t->text('Binary label', '0.1 in binary', 110, 800, 860, 60, 21.3, 9.7, ['size' => 34, 'color' => '#fecdd3', 'bold' => false]);
        $t->text('Binary digits', '0.0001100110011001...', 90, 870, 900, 130, 21.7, 9.3, ['size' => 46, 'font' => 'mono', 'color' => '#fb7185', 'reveal' => 'type', 'cps' => 14]);
        $t->text('Never ends', 'It never ends, so the computer has to cut it off.', 70, 1090, 940, 200, 26.0, 5, ['size' => 46, 'color' => '#e2e8f0', 'bold' => false, 'reveal' => 'type', 'cps' => 20]);

        // Scene 3: what to do about it.
        $t->say(31.3, 'So what can you do? For money, use a decimal type.');
        $t->text('Python chip 2', 'Python', 70, 380, 220, 60, 31.0, 15, ['size' => 32, 'fill' => '#3776ab', 'color' => '#ffd343', 'radius' => 30, 'enter' => 'pop']);
        $t->code('Decimal code', "from decimal import Decimal as D\nprint(D(\"0.1\") + D(\"0.2\"))", 'python', 70, 450, 940, 270, 31.4, 14.6, ['cps' => 18, 'size' => 40, 'enter' => 'pop']);
        $good = $t->text('Good output', '0.3', 40, 760, 1000, 160, 35.2, 10.8, ['size' => 120, 'font' => 'mono', 'color' => '#4ade80', 'enter' => 'pop']);
        $t->pulse($good, 36.0, 1.12, 0.7);

        $t->say(36.5, 'And never compare floats with double equals. Check that they are close enough.');
        $t->text('Compare label', 'Compare like this', 70, 1010, 940, 70, 37.0, 9, ['size' => 36, 'color' => '#99f6e4', 'bold' => false, 'enter' => 'fade']);
        $t->code('Close enough', 'abs(a - b) < 1e-9', 'python', 70, 1090, 940, 225, 37.4, 8.6, ['cps' => 14, 'size' => 46, 'enter' => 'pop']);

        $t->say(42.0, 'Follow for more coding surprises.');
        $t->text('Follow', 'Follow for more coding surprises', 70, 1400, 940, 160, 42.4, 3.6, ['size' => 52, 'color' => '#facc15', 'enter' => 'pop']);

        return $t->toArray();
    }

    /**
     * Plates drop onto a stack and get taken off again, next to the matching push and pop calls.
     *
     * @return array<string, mixed>
     */
    private static function stackOfPlates(): array
    {
        $t = new Timeline(43, '#1c1405', '#3a2a0a');

        $t->text('Title', 'The stack, explained with plates', 70, 90, 940, 230, 0.2, 42.8, ['size' => 62, 'enter' => 'slide']);
        $t->say(0.5, 'Imagine a stack of plates.');
        $t->say(3.4, 'You can only add a plate on the top.');

        $t->box('Table', 90, 1290, 440, 24, 1.0, 24, ['fill' => '#a8a29e', 'radius' => 12, 'enter' => 'pop']);
        $t->box('Terminal', 580, 520, 440, 660, 2.0, 23, ['fill' => '#0b1220', 'stroke' => '#334155', 'strokeWidth' => 4, 'radius' => 32, 'enter' => 'pop']);
        $t->text('Terminal title', 'stack = []', 620, 545, 360, 60, 2.4, 22.6, ['size' => 34, 'font' => 'mono', 'color' => '#94a3b8', 'align' => 'left', 'bold' => false]);

        $plates = [['A', '#ef4444', 5.0, null], ['B', '#3b82f6', 7.5, null], ['C', '#22c55e', 10.0, 19.0], ['D', '#a855f7', 12.5, 16.5]];
        foreach ($plates as $slot => [$letter, $fill, $drop, $pop]) {
            $target = 1214 - 76 * $slot;
            $gone = $pop === null ? 24.8 : $pop + 1.3;
            $plate = $t->text("Plate $letter", $letter, 110, $target, 400, 70, $drop, $gone - $drop, ['size' => 38, 'fill' => $fill, 'radius' => 20, 'exit' => 'fade']);
            $t->key($plate, 'y', [[$drop, -200, 'bounce'], [$drop + 0.9, $target]]);

            if ($pop !== null) {
                $t->key($plate, 'y', [[$pop, $target, 'ease-in'], [$pop + 1.1, 260]]);
                $t->key($plate, 'rotation', [[$pop, 0, 'ease-in'], [$pop + 1.1, 25]]);
            }

            $t->text("Push $letter", "push(\"$letter\")", 620, 640 + $slot * 90, 380, 70, $drop, 24.8 - $drop, ['size' => 40, 'font' => 'mono', 'color' => '#4ade80', 'align' => 'left', 'reveal' => 'type', 'cps' => 12]);
        }
        $t->text('Pop 1', 'pop()', 620, 1000, 380, 70, 16.4, 8.4, ['size' => 40, 'font' => 'mono', 'color' => '#f87171', 'align' => 'left', 'reveal' => 'type', 'cps' => 10]);
        $t->text('Pop 2', 'pop()', 620, 1090, 380, 70, 18.9, 5.9, ['size' => 40, 'font' => 'mono', 'color' => '#f87171', 'align' => 'left', 'reveal' => 'type', 'cps' => 10]);

        $t->say(8.2, 'Add another, and another.');
        $t->say(15.3, 'Now to take a plate away, you must take the top one first.');
        $t->say(20.6, 'The last plate in is the first plate out.');
        $banner = $t->text('LIFO', 'Last In, First Out', 90, 1380, 940, 110, 21.0, 4.0, ['size' => 60, 'color' => '#facc15', 'enter' => 'pop']);
        $t->pulse($banner, 21.8, 1.1, 0.8);

        // Part two: where stacks hide in everyday software.
        $t->text('Everywhere', 'Stacks are everywhere', 70, 420, 940, 120, 25.2, 17.8, ['size' => 64, 'color' => '#fbbf24', 'enter' => 'pop']);
        foreach ([
            ["Undo (Ctrl+Z)\nThe last change comes off first", '#b45309', 26.0, 600],
            ["Browser back button\nThe last page you saw comes first", '#0e7490', 29.4, 830],
            ["Function calls\nThe last one called finishes first", '#7c3aed', 33.0, 1060],
        ] as [$label, $fill, $at, $y]) {
            $t->text('Example '.strtok($label, "\n"), $label, 70, $y, 940, 190, $at, 43 - $at, ['size' => 40, 'fill' => $fill, 'radius' => 36, 'padding' => 40, 'align' => 'left', 'enter' => 'slide']);
        }
        $t->say(25.4, 'Stacks are everywhere. Undo is one.');
        $t->say(29.6, 'The back button in your browser is a stack.');
        $t->say(33.6, 'And every function call goes on a stack.');
        $t->say(37.9, 'Simple idea, powerful tool. Follow for more.');
        $t->text('Follow', 'Follow for more data structures', 90, 1340, 900, 120, 38.5, 4.5, ['size' => 50, 'color' => '#fde68a', 'enter' => 'pop']);

        return $t->toArray();
    }
}
