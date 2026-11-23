<?php

namespace Database\Seeders\Showcase;

use App\Enums\ProjectType;

/**
 * Batch 2: five more hand-scripted videos. A hash table that finds keys, the call stack in recursion,
 * Git merge against rebase, a rapid-fire JavaScript quiz, and SQL joins shown with moving rows.
 */
final class BatchTwo
{
    /**
     * @return list<array{name: string, type: ProjectType, category: string, language?: string, tags: list<string>, upload: array{title: string, description: string, hashtags: list<string>}, document: array<string, mixed>}>
     */
    public static function projects(): array
    {
        return [
            [
                'name' => 'How a dictionary finds things instantly',
                'type' => ProjectType::Explain, 'category' => 'Data structures', 'language' => 'python', 'tags' => ['hash-table', 'dictionary', 'data-structures', 'interview'],
                'upload' => [
                    'title' => 'How a Python dictionary finds things instantly (hash tables explained)',
                    'description' => "Why is looking something up in a dictionary so fast?\n\nBecause of a hash function: it turns your key into a number, and that number is the bucket where the value lives. If two keys land in the same bucket, that is a collision, and the bucket keeps a small list.\n\nThat is why dictionaries and sets are so fast.\n\nFollow for more data structures explained visually!",
                    'hashtags' => ['shorts', 'coding', 'programming', 'python', 'hashtable', 'datastructures', 'interviewprep', 'learntocode'],
                ],
                'document' => self::hashTable(),
            ],
            [
                'name' => 'Recursion: the call stack, step by step',
                'type' => ProjectType::Explain, 'category' => 'Computer science', 'language' => 'python', 'tags' => ['recursion', 'call-stack', 'factorial', 'beginner'],
                'upload' => [
                    'title' => 'Recursion explained: watch the call stack grow and unwind',
                    'description' => "Recursion means a function that calls itself. But how does the computer keep track of every call?\n\nWatch fact(3) build a stack of frames, hit the base case, and unwind back to 6. Then see what happens when you forget the base case: a RecursionError.\n\nAlways write the base case first!\n\nFollow for more computer science in under a minute.",
                    'hashtags' => ['shorts', 'coding', 'programming', 'recursion', 'callstack', 'python', 'computerscience', 'beginner'],
                ],
                'document' => self::recursion(),
            ],
            [
                'name' => 'Git merge vs rebase',
                'type' => ProjectType::Compare, 'category' => 'Git and tools', 'tags' => ['git', 'merge', 'rebase', 'interview'],
                'upload' => [
                    'title' => 'Git merge vs rebase: what is the difference? (animated)',
                    'description' => "Same goal, different history.\n\nMerge keeps the full history and adds a merge commit. Rebase replays your commits on top of the latest main for a straight line, but rewrites them as new commits.\n\nRule of thumb: merge on shared branches, rebase only your own. Never rebase commits other people already pulled.\n\nWhich do you use? Comment below!\n\nFollow for more Git explained simply.",
                    'hashtags' => ['shorts', 'coding', 'programming', 'git', 'github', 'devops', 'interviewprep', 'learntocode'],
                ],
                'document' => self::gitBranches(),
            ],
            [
                'name' => 'JavaScript true or false: == vs ===',
                'type' => ProjectType::Code, 'category' => 'JavaScript', 'language' => 'javascript', 'tags' => ['javascript', 'equality', 'gotcha', 'quiz'],
                'upload' => [
                    'title' => 'JavaScript quiz: true or false? 5 tricky comparisons (== vs ===)',
                    'description' => "Five quick JavaScript comparisons. Pause during the countdown and comment your answer before the reveal!\n\n0 == \"0\"\n0 === \"0\"\nnull == undefined\n[] == false\nNaN === NaN\n\nHow many did you get right? Always use === and the surprises disappear.\n\nFollow for a new quiz every day!",
                    'hashtags' => ['shorts', 'coding', 'programming', 'javascript', 'quiz', 'webdevelopment', 'interviewprep', 'gotcha'],
                ],
                'document' => self::truthQuiz(),
            ],
            [
                'name' => 'SQL joins explained with moving rows',
                'type' => ProjectType::Explain, 'category' => 'Databases and SQL', 'language' => 'sql', 'tags' => ['sql', 'join', 'database', 'interview'],
                'upload' => [
                    'title' => 'SQL JOINs explained: INNER vs LEFT vs RIGHT with moving rows',
                    'description' => "Watch rows move from two tables into the result.\n\nINNER JOIN keeps only the rows that match on both sides. LEFT JOIN keeps every row from the left table, even without a match. RIGHT JOIN keeps every row from the right table.\n\nUnmatched rows show up as NULL.\n\nFollow for more SQL explained visually!",
                    'hashtags' => ['shorts', 'coding', 'programming', 'sql', 'database', 'join', 'mysql', 'interviewprep'],
                ],
                'document' => self::sqlJoins(),
            ],
        ];
    }

    /**
     * Keys go through a hash function and land in buckets, including one collision, then a lookup.
     *
     * @return array<string, mixed>
     */
    private static function hashTable(): array
    {
        $t = new Timeline(44, '#07191b', '#0c3a3d');

        $t->text('Title', 'How a dictionary finds things instantly', 70, 90, 940, 220, 0.2, 43.8, ['size' => 60, 'enter' => 'slide']);
        $t->say(0.5, 'How does a Python dictionary find things instantly?');

        for ($i = 0; $i < 5; $i++) {
            $y = 520 + $i * 150;
            $at = 1.0 + $i * 0.15;
            $t->text("Index $i", (string) $i, 560, $y + 25, 60, 60, $at, 43 - $at, ['size' => 44, 'color' => '#5eead4', 'bold' => false]);
            $t->box("Bucket $i", 640, $y, 360, 110, $at, 43 - $at, ['fill' => '#12302f', 'stroke' => '#2dd4bf', 'strokeWidth' => 4, 'radius' => 24, 'enter' => 'pop']);
        }
        $t->text('Hash function', 'hash()', 300, 800, 250, 150, 3.0, 40.8, ['size' => 50, 'fill' => '#7c3aed', 'radius' => 32, 'font' => 'mono', 'enter' => 'pop']);
        $t->say(5.0, 'It runs your key through a hash function, which turns it into a number.');

        // [name, fill, ink, bucket, first moment, slot inside the bucket]
        $keys = [['apple', '#ef4444', '#ffffff', 1, 7.0, 0], ['banana', '#facc15', '#1a1a1a', 3, 15.0, 0], ['cherry', '#ec4899', '#ffffff', 1, 19.5, 1]];

        foreach ($keys as [$name, $fill, $ink, $bucket, $s, $slot]) {
            $chip = $t->text("Key $name", $name, 40, 835, 220, 80, $s, 2.0, ['size' => 40, 'fill' => $fill, 'color' => $ink, 'radius' => 24, 'enter' => 'pop', 'exit' => 'fade']);
            $t->fly($chip, 40, 835, 320, 835, $s + 0.4, $s + 1.4);
            $t->text("Index of $name", "= $bucket", 300, 720, 250, 70, $s + 1.5, 1.9, ['size' => 56, 'color' => '#facc15', 'font' => 'mono', 'enter' => 'pop', 'exit' => 'fade']);

            $bucketTop = 520 + $bucket * 150;
            $t->arrow("To bucket $name", 550, 875, 640, $bucketTop + 55, $s + 2.0, 2.2, ['stroke' => $fill, 'strokeWidth' => 8, 'draw' => 0.5, 'exit' => 'fade']);

            $slotX = 655 + $slot * 180;
            $placed = $t->text("Stored $name", $name, 560, 835, 150, 80, $s + 2.3, 43.5 - ($s + 2.3), ['size' => 34, 'fill' => $fill, 'color' => $ink, 'radius' => 20, 'enter' => 'fade']);
            $t->fly($placed, 560, 835, $slotX, $bucketTop + 15, $s + 2.4, $s + 3.3);
        }

        $t->say(11.0, 'That number is the bucket where the value is stored.');
        $t->say(18.8, 'A different key might land in the same bucket. That is a collision.');
        $t->arrow('Chain link', 805, 725, 833, 725, 23.2, 20.8, ['stroke' => '#ffffff', 'strokeWidth' => 6, 'draw' => 0.3]);
        $stamp = $t->text('Collision', 'COLLISION!', 200, 1060, 330, 90, 23.0, 3.4, ['size' => 40, 'fill' => '#dc2626', 'radius' => 24, 'rotation' => -6, 'enter' => 'pop', 'exit' => 'fade']);
        $t->shake($stamp, 23.6, 4, 0.8);
        $t->say(25.0, 'The bucket just keeps a small list. Collisions are rare, so it stays fast.');

        // Looking banana up again.
        $t->say(31.2, 'To look up banana, hash it again. Same number, same bucket. Found in one step.');
        $t->text('Lookup label', 'Lookup: banana', 70, 1290, 940, 80, 31.5, 8.5, ['size' => 52, 'color' => '#facc15', 'enter' => 'pop']);
        $look = $t->text('Lookup key', 'banana', 40, 835, 220, 80, 32.0, 2.0, ['size' => 40, 'fill' => '#facc15', 'color' => '#1a1a1a', 'radius' => 24, 'enter' => 'pop', 'exit' => 'fade']);
        $t->fly($look, 40, 835, 320, 835, 32.3, 33.2);
        $t->text('Lookup index', '= 3', 300, 720, 250, 70, 33.3, 2.4, ['size' => 56, 'color' => '#facc15', 'font' => 'mono', 'enter' => 'pop', 'exit' => 'fade']);
        $t->arrow('Lookup arrow', 550, 875, 640, 1025, 33.9, 2.6, ['stroke' => '#facc15', 'strokeWidth' => 8, 'draw' => 0.5, 'exit' => 'fade']);
        $glow = $t->box('Found glow', 640, 970, 360, 110, 34.6, 8, ['fill' => null, 'stroke' => '#4ade80', 'strokeWidth' => 8, 'radius' => 24, 'enter' => 'pop']);
        $t->blink($glow, 35.0, 3, 0.3);
        $t->code('Lookup code', 'prices["banana"]', 'python', 70, 1390, 940, 220, 33.1, 7.4, ['size' => 46, 'cps' => 14, 'enter' => 'pop']);
        $t->text('Found', 'Found in 1 step', 70, 1630, 940, 80, 35.6, 4.4, ['size' => 52, 'color' => '#4ade80', 'enter' => 'pop']);

        $t->say(38.6, 'That is why dictionaries and sets are so fast. Follow for more.');
        $t->text('Outro', 'Hashing = instant lookups', 70, 1290, 940, 100, 40.2, 3.6, ['size' => 54, 'color' => '#5eead4', 'enter' => 'pop']);

        return $t->toArray();
    }

    /**
     * factorial(3) builds three stack frames, returns 1, 2, 6, then a missing base case overflows the stack.
     *
     * @return array<string, mixed>
     */
    private static function recursion(): array
    {
        $t = new Timeline(44, '#12081f', '#2a0f4a');

        $t->text('Title', 'Recursion: the call stack', 70, 90, 940, 150, 0.2, 43.8, ['size' => 64, 'enter' => 'slide']);
        $t->say(0.5, 'Recursion is a function that calls itself. How does the computer keep track?');

        $t->code('Code', "def fact(n):\n    if n == 1:\n        return 1\n    return n * fact(n - 1)\n\nprint(fact(3))", 'python', 70, 270, 940, 456, 1.0, 42.8, ['size' => 34, 'cps' => 30, 'enter' => 'pop']);

        $mark = $t->box('Current line', 100, 352, 880, 50, 6.8, 21.2, ['fill' => null, 'stroke' => '#facc15', 'strokeWidth' => 5, 'radius' => 12, 'enter' => 'pop', 'exit' => 'fade']);
        $line = fn (int $index) => 357 + 51 * $index - 5;
        $t->key($mark, 'y', [
            [7.0, $line(5), 'ease-in-out'], [8.8, $line(1), 'ease-in-out'], [10.4, $line(3), 'ease-in-out'], [12.2, $line(1), 'ease-in-out'],
            [13.9, $line(3), 'ease-in-out'], [15.0, $line(1), 'ease-in-out'], [17.0, $line(2), 'ease-in-out'], [20.8, $line(3), 'ease-in-out'],
            [24.3, $line(3), 'ease-in-out'], [27.0, $line(5)],
        ]);
        $t->blink($mark, 17.2, 2, 0.3);

        $t->say(6.9, 'Every call gets its own frame on the call stack.');
        $t->say(10.6, 'fact of three calls fact of two.');
        $t->say(13.9, 'Which calls fact of one.');
        $t->say(16.8, 'Base case! It returns one.');
        $t->say(21.0, 'So fact of two returns two times one.');
        $t->say(24.5, 'And fact of three returns six.');
        $t->say(27.5, 'The stack unwinds, one frame at a time.');

        // [label, fill, drop moment, leave moment, result moment, result text]
        $frames = [
            ['fact(3)   n = 3', '#2563eb', 7.6, 26.0, 24.4, '= 6'],
            ['fact(2)   n = 2', '#7c3aed', 11.9, 22.5, 20.9, '= 2'],
            ['fact(1)   n = 1', '#db2777', 14.6, 18.6, 17.2, '= 1'],
        ];
        foreach ($frames as $k => [$label, $fill, $drop, $leave, $answerAt, $answer]) {
            $target = 1420 - 140 * $k;
            $frame = $t->text("Frame $label", $label, 130, $target, 640, 120, $drop, $leave + 0.9 - $drop, ['size' => 44, 'fill' => $fill, 'radius' => 28, 'padding' => 36, 'align' => 'left', 'exit' => 'fade']);
            $t->key($frame, 'y', [[$drop, 780, 'bounce'], [$drop + 0.9, $target]]);
            $t->key($frame, 'x', [[$leave, 130, 'ease-in'], [$leave + 0.9, 1100]]);

            $chip = $t->text("Returns $answer", $answer, 790, $target + 15, 190, 90, $answerAt, $leave + 0.9 - $answerAt, ['size' => 44, 'fill' => '#16a34a', 'radius' => 24, 'enter' => 'pop', 'exit' => 'fade']);
            $t->key($chip, 'x', [[$leave, 790, 'ease-in'], [$leave + 0.9, 1100]]);
        }

        $out = $t->text('Output', 'Output: 6', 70, 1250, 940, 150, 26.6, 4.4, ['size' => 90, 'font' => 'mono', 'color' => '#4ade80', 'enter' => 'pop']);
        $t->pulse($out, 27.4, 1.1, 0.7);

        // No base case: the stack just keeps growing.
        $t->say(31.0, 'Forget the base case, and the stack never stops growing.');
        for ($i = 0; $i < 7; $i++) {
            $drop = 32.0 + $i * 0.45;
            $target = 1560 - 88 * ($i + 1);
            $f = $t->text("Overflow $i", 'fact(n)', 220, $target, 640, 76, $drop, 44 - $drop, ['size' => 32, 'fill' => $i % 2 ? '#b91c1c' : '#dc2626', 'radius' => 20, 'font' => 'mono']);
            $t->key($f, 'y', [[$drop, 760, 'bounce'], [$drop + 0.6, $target]]);
        }
        $say = $t->say(35.5, 'Python stops it with a recursion error.');
        $banner = $t->text('Recursion error', 'RecursionError!', 70, 780, 940, 130, 35.5, 8.5, ['size' => 64, 'font' => 'mono', 'fill' => '#7f1d1d', 'radius' => 28, 'enter' => 'pop']);
        $t->shake($banner, 36.0, 3, 0.9);
        $t->say(39.0, 'Always write the base case first. Follow for more.');
        $t->text('Tip', 'Always write the base case first.', 70, 1560, 940, 110, 39.5, 4.5, ['size' => 46, 'color' => '#facc15', 'enter' => 'pop']);

        return $t->toArray();
    }

    /**
     * Two commit graphs: merge makes a merge commit, rebase replays the commits in a straight line.
     *
     * @return array<string, mixed>
     */
    private static function gitBranches(): array
    {
        $t = new Timeline(42, '#1a0d0a', '#3d1d12');

        $commit = function (string $name, int $cx, int $cy, float $at, string $fill, float $until = 42.0, string $ink = '#ffffff') use ($t) {
            $cy += $cy < 700 ? 90 : 170;
            $dot = $t->circle("Commit $name", $cx - 34, $cy - 34, 68, 68, $at, $until - $at, ['fill' => $fill, 'enter' => 'pop']);
            $t->text("Label $name", $name, $cx - 34, $cy - 34, 68, 68, $at, $until - $at, ['size' => 26, 'color' => $ink, 'enter' => 'pop']);

            return $dot;
        };
        $link = fn (string $name, int $x1, int $y1, int $x2, int $y2, float $at, string $stroke, float $until = 42.0) => $t->arrow($name, $x1, $y1 + ($y1 < 700 ? 90 : 170), $x2, $y2 + ($y2 < 700 ? 90 : 170), $at, $until - $at, ['stroke' => $stroke, 'strokeWidth' => 7, 'head' => false, 'draw' => 0.5]);

        $t->text('Title', 'Git merge vs rebase', 70, 90, 940, 150, 0.2, 41.8, ['size' => 68, 'enter' => 'slide']);
        $t->say(0.5, 'Git merge or rebase? Same goal, different history.');

        // Merge
        $t->text('Merge label', 'MERGE', 70, 390, 400, 70, 2.0, 40, ['size' => 52, 'color' => '#38bdf8', 'align' => 'left', 'enter' => 'slide']);
        foreach ([['M1', 110, 3.0], ['M2', 290, 3.6], ['M3', 470, 4.2]] as [$name, $x, $at]) {
            $commit($name, $x, 520, $at, '#0284c7');
        }
        $link('Main link 1', 144, 520, 256, 520, 3.4, '#475569');
        $link('Main link 2', 324, 520, 436, 520, 4.0, '#475569');
        $link('Feature fork', 318, 500, 440, 438, 5.4, '#b45309');
        $commit('F1', 470, 410, 5.6, '#f59e0b');
        $link('Feature link', 504, 410, 616, 410, 6.4, '#b45309');
        $commit('F2', 650, 410, 6.6, '#f59e0b');

        $t->say(5.7, 'Merge ties two branches together with a new commit.');
        $link('Merge from main', 504, 520, 796, 520, 8.4, '#475569');
        $link('Merge from feature', 676, 438, 808, 494, 8.5, '#b45309');
        $merge = $commit('MM', 830, 520, 8.8, '#a855f7');
        $t->pulse($merge, 9.2, 1.25, 0.8);
        $t->text('Merge caption', 'Keeps the full history. Adds a merge commit.', 70, 700, 940, 90, 9.8, 32.2, ['size' => 36, 'bold' => false, 'color' => '#cbd5e1', 'reveal' => 'type', 'cps' => 28]);

        // Rebase
        $t->say(13.5, 'Rebase takes your commits and replays them on top of the latest main.');
        $t->text('Rebase label', 'REBASE', 70, 930, 400, 70, 13.0, 29, ['size' => 52, 'color' => '#4ade80', 'align' => 'left', 'enter' => 'slide']);
        foreach ([['M1', 110, 13.6], ['M2', 290, 14.0], ['M3', 470, 14.4]] as [$name, $x, $at]) {
            $commit($name, $x, 980, $at, '#0284c7');
        }
        $link('Rebase main 1', 144, 980, 256, 980, 13.8, '#475569');
        $link('Rebase main 2', 324, 980, 436, 980, 14.2, '#475569');
        $link('Rebase fork', 318, 960, 440, 898, 15.2, '#b45309', 19.5);
        $commit('F1', 470, 870, 15.4, '#f59e0b', 19.5);
        $link('Rebase feature link', 504, 870, 616, 870, 16.2, '#b45309', 19.5);
        $commit('F2', 650, 870, 16.4, '#f59e0b', 19.5);

        // The two feature commits are copied onto the end of main.
        $link('New link 1', 504, 980, 616, 980, 19.8, '#16a34a');
        $link('New link 2', 684, 980, 796, 980, 20.4, '#16a34a');
        $first = $commit("F1'", 650, 980, 19.6, '#22c55e');
        $second = $commit("F2'", 830, 980, 20.2, '#22c55e');
        $t->pulse($first, 20.0, 1.2, 0.6);
        $t->pulse($second, 20.6, 1.2, 0.6);
        $t->say(21.0, 'The result is one straight line.');
        $t->say(24.0, 'But those commits are new copies, so history is rewritten.');
        $t->text('Rebase caption', 'Straight history. Rewrites commits into new copies.', 70, 1240, 940, 90, 22.0, 20, ['size' => 36, 'bold' => false, 'color' => '#cbd5e1', 'reveal' => 'type', 'cps' => 26]);

        // Rule of thumb
        $t->say(28.2, 'A simple rule: merge on shared branches. Rebase only your own.');
        $t->box('Rule card', 70, 1340, 940, 290, 28.0, 14, ['fill' => '#241410', 'stroke' => '#f59e0b', 'strokeWidth' => 4, 'radius' => 32, 'enter' => 'pop']);
        $t->text('Rule text', "Rule of thumb\nShared branch: merge\nYour own branch: rebase", 110, 1355, 860, 260, 28.4, 13.6, ['size' => 46, 'align' => 'left', 'reveal' => 'type', 'cps' => 24]);
        $t->say(33.6, 'And never rebase commits that other people already pulled.');
        $warn = $t->text('Warning', 'Never rebase commits others already pulled', 70, 1650, 940, 110, 34.0, 6.5, ['size' => 38, 'fill' => '#7f1d1d', 'radius' => 30, 'padding' => 24, 'enter' => 'pop']);
        $t->shake($warn, 34.6, 3, 0.8);
        $t->say(38.6, 'Follow for more Git explained simply.');

        return $t->toArray();
    }

    /**
     * Five rapid-fire comparisons with a countdown, a big TRUE or FALSE, and the reason.
     *
     * @return array<string, mixed>
     */
    private static function truthQuiz(): array
    {
        $t = new Timeline(47, '#0f1b0a', '#27401a');

        $t->text('Title', 'True or false?', 70, 90, 940, 150, 0.2, 46.8, ['size' => 84, 'enter' => 'slide']);
        $t->say(0.5, 'Five quick JavaScript comparisons. True or false?');

        // [code, answer, spoken reason, shown reason]
        $rounds = [
            ['0 == "0"', true, 'True. JavaScript turns the string into a number first.', 'The string "0" becomes the number 0.'],
            ['0 === "0"', false, 'False. Triple equals checks the type too.', 'Different types: number and string.'],
            ['null == undefined', true, 'True. Null and undefined are loosely equal.', 'A special rule: only equal to each other.'],
            ['[] == false', true, 'True. Both sides turn into zero.', 'Empty array becomes "", then 0. False is 0.'],
            ['NaN === NaN', false, 'False. NaN is never equal to anything.', 'NaN is not equal to anything, even NaN.'],
        ];

        foreach ($rounds as $index => [$code, $answer, $spoken, $reason]) {
            $from = 5.5 + $index * 7.2;
            $reveal = $from + 4.6;
            $until = $from + 7.2;
            $n = $index + 1;

            $t->text("Round $n", "Round $n of 5", 340, 400, 400, 64, $from, $until - $from, ['size' => 32, 'fill' => '#65a30d', 'radius' => 32, 'enter' => 'pop']);
            $t->code("Expression $n", $code, 'javascript', 70, 520, 940, 234, $from + 0.2, $until - $from - 0.2, ['size' => 56, 'cps' => 24, 'enter' => 'pop']);
            $t->text("Ask $n", 'True or false?', 70, 820, 940, 110, $from + 1.4, $reveal - $from - 1.4, ['size' => 66, 'color' => '#facc15', 'enter' => 'pop']);

            foreach ([3, 2, 1] as $position => $number) {
                $c = $t->text("Count $n-$number", (string) $number, 390, 970, 300, 240, $from + 1.6 + $position, 1.0, [
                    'size' => 210, 'color' => ['#4ade80', '#fbbf24', '#f87171'][$position], 'enter' => 'pop',
                ]);
                $t->pulse($c, $from + 1.65 + $position, 1.08, 0.5);
            }

            $big = $t->text("Answer $n", $answer ? 'TRUE' : 'FALSE', 190, 980, 700, 200, $reveal, $until - $reveal, ['size' => 130, 'fill' => $answer ? '#16a34a' : '#dc2626', 'radius' => 48, 'enter' => 'pop']);
            $t->pulse($big, $reveal + 0.4, 1.06, 0.6);
            $t->text("Why $n", $reason, 70, 1240, 940, 220, $reveal + 0.5, $until - $reveal - 0.5, ['size' => 44, 'bold' => false, 'color' => '#e2e8f0', 'reveal' => 'type', 'cps' => 26]);
            $t->say($reveal, $spoken);
        }

        $t->say(43.5, 'Moral: use triple equals.');
        $t->text('Moral', 'Use === and the surprises disappear', 70, 600, 940, 200, 41.8, 5.2, ['size' => 60, 'color' => '#facc15', 'enter' => 'pop']);
        $t->code('Moral code', "if (a === b) {\n  // safe\n}", 'javascript', 70, 940, 940, 321, 42.6, 4.4, ['size' => 56, 'cps' => 24, 'enter' => 'pop']);

        return $t->toArray();
    }

    /**
     * Rows from a users table and an orders table move into a result for INNER, LEFT and RIGHT joins.
     *
     * @return array<string, mixed>
     */
    private static function sqlJoins(): array
    {
        $t = new Timeline(45, '#150f05', '#37260a');

        $t->text('Title', 'SQL joins, with moving rows', 70, 70, 940, 130, 0.2, 44.8, ['size' => 56, 'enter' => 'slide']);
        $t->say(0.5, 'A SQL join combines rows from two tables.');

        // The two source tables stay on screen the whole time.
        $t->text('Users header', 'users', 70, 680, 400, 70, 2.0, 43, ['size' => 34, 'fill' => '#2563eb', 'radius' => 20, 'enter' => 'pop']);
        $t->text('Orders header', 'orders', 610, 680, 400, 70, 2.3, 42.7, ['size' => 34, 'fill' => '#ea580c', 'radius' => 20, 'enter' => 'pop']);
        $users = [];
        $orders = [];
        foreach ([['1  Ann', 770], ['2  Bob', 870], ['3  Cy', 970]] as $i => [$label, $y]) {
            $users[$label] = $t->text("User $label", $label, 70, $y, 400, 80, 2.6 + $i * 0.2, 42.4 - $i * 0.2, ['size' => 40, 'fill' => '#1e3a8a', 'radius' => 18, 'align' => 'left', 'padding' => 28, 'enter' => 'slide']);
        }
        foreach ([['user 1 · book', 770], ['user 3 · pen', 870], ['user 4 · cup', 970]] as $i => [$label, $y]) {
            $orders[$label] = $t->text("Order $label", $label, 610, $y, 400, 80, 3.0 + $i * 0.2, 42 - $i * 0.2, ['size' => 40, 'fill' => '#9a3412', 'radius' => 18, 'align' => 'left', 'padding' => 28, 'enter' => 'slide']);
        }

        // Each scene: [name, join word, start, end, spoken line, result rows, which source rows to flash]
        $scenes = [
            ['inner', 'INNER', 6.0, 17.0, 'Inner join keeps only the rows that match on both sides.', ['Ann · book', 'Cy · pen'], [$users['2  Bob'], $orders['user 4 · cup']]],
            ['left', 'LEFT', 17.0, 28.0, 'Left join keeps every row from the left table, even without a match.', ['Ann · book', 'Bob · NULL', 'Cy · pen'], [$users['2  Bob']]],
            ['right', 'RIGHT', 28.0, 38.0, 'Right join does the opposite. Every order is kept, even for an unknown user.', ['Ann · book', 'Cy · pen', 'NULL · cup'], [$orders['user 4 · cup']]],
        ];

        foreach ($scenes as [$name, $word, $from, $to, $spoken, $rows, $flash]) {
            $length = $to - $from;
            $t->say($from, $spoken);
            $t->code("Join $name", "SELECT name, item\nFROM users\n$word JOIN orders ON id = user_id;", 'sql', 70, 270, 940, 312, $from + 0.3, $length - 0.3, ['size' => 36, 'cps' => 26, 'enter' => 'pop']);

            // The rows that match are linked by arrows.
            $t->arrow("Match 1 $name", 470, 810, 610, 810, $from + 3.0, $length - 3.0, ['stroke' => '#facc15', 'strokeWidth' => 7, 'draw' => 0.5, 'exit' => 'fade']);
            $t->arrow("Match 2 $name", 470, 1010, 610, 910, $from + 3.4, $length - 3.4, ['stroke' => '#facc15', 'strokeWidth' => 7, 'draw' => 0.5, 'exit' => 'fade']);
            foreach ($flash as $id) {
                $t->blink($id, $from + 4.0, 3, 0.3);
            }

            $t->text("Result header $name", "result ($word)", 210, 1130, 660, 70, $from + 4.6, $length - 4.6, ['size' => 34, 'fill' => '#15803d', 'radius' => 20, 'enter' => 'pop']);
            foreach ($rows as $i => $row) {
                $isNull = str_contains($row, 'NULL');
                $t->text("Result $name $i", $row, 210, 1220 + $i * 90, 660, 80, $from + 5.2 + $i * 0.8, $length - 5.2 - $i * 0.8, [
                    'size' => 38, 'fill' => $isNull ? '#374151' : '#14532d', 'radius' => 18, 'align' => 'left', 'padding' => 30, 'enter' => 'slide', 'italic' => $isNull,
                ]);
            }
        }

        $t->say(38.1, 'Inner, matches. Left, all left rows. Right, all right rows.');
        $t->box('Cheat card', 70, 300, 940, 800, 38.0, 6.8, ['fill' => '#241a08', 'stroke' => '#f59e0b', 'strokeWidth' => 4, 'radius' => 32, 'enter' => 'pop']);
        $t->text('Cheat sheet', "INNER\nonly the matches\n\nLEFT\nall from the left table\n\nRIGHT\nall from the right table", 110, 330, 860, 740, 38.4, 6.4, ['size' => 50, 'align' => 'left', 'reveal' => 'type', 'cps' => 30]);

        return $t->toArray();
    }
}
