<?php

namespace Database\Seeders;

use App\Enums\ProjectType;
use App\Models\Project;
use App\Services\ProjectTemplates;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * About forty ready-made studio videos across many topics, so the Studio list, filters and
 * editor have real material to work with. Safe to run again: projects that already exist
 * (matched by name) are left alone, so your own edits are never overwritten.
 *
 * Run with: php artisan db:seed --class=ProjectSeeder
 */
class ProjectSeeder extends Seeder
{
    /**
     * Picture badges for the picture videos: file name => [label, background, text colour].
     *
     * @var array<string, array{0: string, 1: string, 2: string}>
     */
    private const BADGES = [
        'python' => ['Py', '#3776ab', '#ffd343'],
        'js' => ['JS', '#f7df1e', '#1a1a1a'],
        'php' => ['PHP', '#777bb4', '#ffffff'],
        'go' => ['Go', '#00add8', '#ffffff'],
        'java' => ['Java', '#e76f00', '#ffffff'],
        'html' => ['HTML', '#e34f26', '#ffffff'],
        'css' => ['CSS', '#1572b6', '#ffffff'],
        'sql' => ['SQL', '#e48e00', '#ffffff'],
        'git' => ['Git', '#f05032', '#ffffff'],
        'db' => ['DB', '#16a34a', '#ffffff'],
        'cloud' => ['API', '#0ea5e9', '#ffffff'],
        'bug' => ['Bug', '#dc2626', '#ffffff'],
    ];

    public function run(): void
    {
        foreach ($this->definitions() as $definition) {
            $project = Project::firstOrCreate(['name' => $definition['name']], [
                'type' => $definition['type'],
                'category' => $definition['category'],
                'language' => $definition['language'] ?? null,
                'tags' => $definition['tags'],
                'document' => $definition['document'],
            ]);

            foreach ($this->assetsUsedBy($definition['document']) as $asset) {
                $this->writeBadge($project, $asset);
            }
        }
    }

    /**
     * @return list<array{name: string, type: ProjectType, category: string, language?: string, tags: list<string>, document: array<string, mixed>}>
     */
    private function definitions(): array
    {
        $code = fn (string $name, string $category, string $language, array $tags, string $snippet, string $result, string $label = 'Output') => [
            'name' => $name, 'type' => ProjectType::Code, 'category' => $category, 'language' => $language, 'tags' => $tags,
            'document' => ProjectTemplates::codeVideo($name, $language, $snippet, $result, $label),
        ];
        $compare = fn (string $name, string $category, array $tags, string $left, string $right, array $leftPoints, array $rightPoints, ?string $language = null) => [
            'name' => $name, 'type' => ProjectType::Compare, 'category' => $category, 'language' => $language, 'tags' => $tags,
            'document' => ProjectTemplates::compareVideo($name, $left, $right, $leftPoints, $rightPoints),
        ];
        $flow = fn (string $name, string $category, array $tags, string $item, string $target, string $note, ?string $language = null) => [
            'name' => $name, 'type' => ProjectType::Explain, 'category' => $category, 'language' => $language, 'tags' => $tags,
            'document' => ProjectTemplates::flowVideo($name, $item, $target, $note),
        ];
        $tips = fn (string $name, string $category, array $tags, array $points, ?string $language = null) => [
            'name' => $name, 'type' => ProjectType::Tips, 'category' => $category, 'language' => $language, 'tags' => $tags,
            'document' => ProjectTemplates::tipsVideo($name, $points),
        ];
        $pictures = fn (string $name, string $category, array $tags, string $caption, array $badges) => [
            'name' => $name, 'type' => ProjectType::Pictures, 'category' => $category, 'tags' => $tags,
            'document' => ProjectTemplates::picturesVideo($name, $caption, array_map(
                fn (string $key) => ['asset' => $key.'.png', 'label' => $this->label($key)],
                $badges,
            )),
        ];

        return [
            // Typing code (12)
            $code('Reverse a string in one line', 'Python basics', 'python', ['strings', 'slicing', 'beginner'], "word = \"python\"\nprint(word[::-1])", 'nohtyp'),
            $code('List comprehension in action', 'Python basics', 'python', ['lists', 'loops', 'beginner'], "sq = [n * n for n in range(5)]\nprint(sq)", '[0, 1, 4, 9, 16]'),
            $code('Swap two variables', 'Python basics', 'python', ['variables', 'beginner', 'tricks'], "a, b = 1, 2\na, b = b, a\nprint(a, b)", '2 1'),
            $code('The mutable default trap', 'Interview prep', 'python', ['functions', 'gotcha', 'interview'], "def add(x, bag=[]):\n    bag.append(x)\n    return bag\nprint(add(1), add(2))", '[1, 2] [1, 2]', 'Surprise! Output'),
            $code('Dictionary get with a default', 'Data structures', 'python', ['dictionaries', 'beginner'], "ages = {\"ann\": 31}\nprint(ages.get(\"bob\", 0))", '0'),
            $code('Sum an array with reduce', 'JavaScript', 'javascript', ['arrays', 'functional'], "const total = [1, 2, 3]\n  .reduce((a, b) => a + b, 0);\nconsole.log(total);", '6'),
            $code('typeof null is a famous bug', 'Interview prep', 'javascript', ['types', 'gotcha', 'interview'], 'console.log(typeof null);', 'object', 'Surprise! Output'),
            $code('Truthy or falsy?', 'JavaScript', 'javascript', ['types', 'gotcha'], "console.log(Boolean(\"0\"));\nconsole.log(Boolean(\"\"));", 'true  false'),
            $code('Map doubles every item', 'JavaScript', 'javascript', ['arrays', 'functional', 'beginner'], "const n = [1, 2, 3];\nconst d = n.map(x => x * 2);\nconsole.log(d);", '[2, 4, 6]'),
            $code('PHP null coalescing', 'Web and CSS', 'php', ['operators', 'beginner'], "\$name = null;\necho \$name ?? \"guest\";", 'guest'),
            $code('Count active users in SQL', 'Databases and SQL', 'sql', ['queries', 'beginner'], "SELECT COUNT(*)\nFROM users\nWHERE active = 1;", '128', 'Result row'),
            $code('Go: defer runs last', 'Computer science', 'go', ['functions', 'gotcha'], "defer fmt.Println(\"bye\")\nfmt.Println(\"hello\")", 'hello  bye'),

            // Compare two things (8)
            $compare('Python vs JavaScript', 'Computer science', ['languages', 'beginner'], 'Python', 'JavaScript',
                ['Readable syntax', 'Great for data and AI', 'Indentation matters'], ['Runs in every browser', 'Event driven', 'Braces and semicolons']),
            $compare('List vs Tuple', 'Data structures', ['python', 'lists', 'beginner'], 'List', 'Tuple',
                ['Can change', 'Written with [ ]', 'Not a dict key'], ['Cannot change', 'Written with ( )', 'Safe as a dict key'], 'python'),
            $compare('Double vs triple equals', 'JavaScript', ['operators', 'gotcha', 'interview'], '==', '===',
                ['Converts types first', '"5" == 5 is true', 'Surprising'], ['No conversion', '"5" === 5 is false', 'Use this one'], 'javascript'),
            $compare('SQL vs NoSQL', 'Databases and SQL', ['databases', 'architecture'], 'SQL', 'NoSQL',
                ['Tables and rows', 'Strict schema', 'Great for reports'], ['Flexible documents', 'Scales out easily', 'Fast simple lookups']),
            $compare('Git merge vs rebase', 'Git and tools', ['git', 'interview'], 'Merge', 'Rebase',
                ['Keeps full history', 'Adds a merge commit', 'Safe on shared branches'], ['Straight history', 'Rewrites commits', 'Not for shared branches']),
            $compare('Stack vs Queue', 'Data structures', ['data-structures', 'interview', 'beginner'], 'Stack', 'Queue',
                ['Last in, first out', 'Undo history', 'push and pop'], ['First in, first out', 'Print jobs', 'enqueue and dequeue']),
            $compare('TCP vs UDP', 'Computer science', ['networking', 'interview'], 'TCP', 'UDP',
                ['Reliable', 'Keeps order', 'Web pages and email'], ['Fast', 'May drop packets', 'Video calls and games']),
            $compare('Array vs Linked list', 'Data structures', ['data-structures', 'performance'], 'Array', 'Linked list',
                ['Fast index access', 'Slow inserts in the middle', 'Compact in memory'], ['Fast inserts', 'Slow to find item n', 'Extra pointer per item']),

            // Explain with moving pieces (8)
            $flow('Where a variable lives', 'Python basics', ['variables', 'memory', 'beginner'], 'x = 5', 'Memory', 'A variable is a name that points to a value stored in memory.', 'python'),
            $flow('What a function call does', 'Python basics', ['functions', 'beginner'], 'add(2, 3)', 'Function', 'The call sends inputs in and gets one result back.'),
            $flow('How a web request works', 'Web and CSS', ['http', 'web', 'beginner'], 'Request', 'Server', 'The browser asks. The server answers with a page or data.'),
            $flow('Pushing onto a stack', 'Data structures', ['stack', 'data-structures'], 'push(7)', 'Stack', 'The newest item sits on top, so it is the first one out.'),
            $flow('What git commit saves', 'Git and tools', ['git', 'beginner'], 'Changes', 'History', 'A commit saves a snapshot you can always come back to.'),
            $flow('Caching in one picture', 'Computer science', ['cache', 'performance'], 'Data', 'Cache', 'The next read is fast because the answer is already close.'),
            $flow('An API in one minute', 'Web and CSS', ['api', 'json', 'web'], 'JSON', 'Client', 'An API sends data in a shape other programs can read.'),
            $flow('A loop, step by step', 'Python basics', ['loops', 'beginner'], 'i = 0', 'Loop', 'The loop repeats the same steps until its condition stops it.', 'python'),

            // Quick tips (6)
            $tips('5 Python tricks to know', 'Python basics', ['python', 'tricks', 'tips'], [
                'Use f-strings to format text', 'enumerate() gives index and item', 'zip() walks two lists together', 'Use with open() for files', 'Slice with [::-1] to reverse',
            ], 'python'),
            $tips('Git commands you need', 'Git and tools', ['git', 'tips', 'beginner'], [
                'git status shows what changed', 'git add . stages everything', 'git commit -m saves a snapshot', 'git pull gets new work', 'git push shares your work',
            ]),
            $tips('Clean code habits', 'Career and habits', ['clean-code', 'habits', 'tips'], [
                'Name things clearly', 'Keep functions small', 'Delete dead code', 'Write a test for each bug',
            ]),
            $tips('A simple debugging checklist', 'Career and habits', ['debugging', 'tips', 'beginner'], [
                'Read the error message slowly', 'Reproduce it every time', 'Print values near the bug', 'Change one thing at a time', 'Explain it to a rubber duck',
            ]),
            $tips('Big O cheat sheet', 'Algorithms', ['big-o', 'interview', 'performance'], [
                'O(1): jump straight to an item', 'O(log n): binary search', 'O(n): one loop', 'O(n log n): good sorting', 'O(n squared): nested loops',
            ]),
            $tips('CSS layout tricks', 'Web and CSS', ['css', 'layout', 'tips'], [
                'display: flex centres things', 'gap beats margins', 'min-height: 100vh fills a screen', 'Use rem for sizes',
            ]),

            // Pictures that fly in (4)
            $pictures('Meet the languages', 'Computer science', ['languages', 'beginner'], 'Pick one and build something small every week.', ['python', 'js', 'php', 'go']),
            $pictures('The web trio', 'Web and CSS', ['html', 'css', 'javascript', 'beginner'], 'HTML is structure, CSS is style, JavaScript is behaviour.', ['html', 'css', 'js']),
            $pictures('Tools every developer uses', 'Git and tools', ['tools', 'git', 'career'], 'Version control, data, APIs and debugging.', ['git', 'db', 'cloud', 'bug']),
            $pictures('Data from database to API', 'Databases and SQL', ['sql', 'api', 'databases'], 'The database stores it, the API shares it.', ['sql', 'db', 'cloud']),
        ];
    }

    private function label(string $key): string
    {
        return [
            'python' => 'Python', 'js' => 'JavaScript', 'php' => 'PHP', 'go' => 'Go', 'java' => 'Java', 'html' => 'HTML', 'css' => 'CSS',
            'sql' => 'SQL', 'git' => 'Git', 'db' => 'Database', 'cloud' => 'API', 'bug' => 'Bugs',
        ][$key] ?? ucfirst($key);
    }

    /**
     * @param  array<string, mixed>  $document
     * @return list<string>
     */
    private function assetsUsedBy(array $document): array
    {
        return collect($document['objects'] ?? [])->pluck('asset')->filter()->unique()->values()->all();
    }

    /**
     * Draw a round badge with see-through corners and save it in the project's pictures folder.
     */
    private function writeBadge(Project $project, string $asset): void
    {
        $key = pathinfo($asset, PATHINFO_FILENAME);

        if (! isset(self::BADGES[$key]) || ! function_exists('imagecreatetruecolor')) {
            return;
        }

        [$text, $background, $foreground] = self::BADGES[$key];
        $big = 800;
        $canvas = imagecreatetruecolor($big, $big);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        imagealphablending($canvas, true);

        [$r, $g, $b] = sscanf($background, '#%02x%02x%02x');
        $fill = imagecolorallocate($canvas, $r, $g, $b);
        $radius = 170;
        imagefilledrectangle($canvas, $radius, 20, $big - $radius, $big - 20, $fill);
        imagefilledrectangle($canvas, 20, $radius, $big - 20, $big - $radius, $fill);
        foreach ([[$radius, $radius], [$big - $radius, $radius], [$radius, $big - $radius], [$big - $radius, $big - $radius]] as [$x, $y]) {
            imagefilledellipse($canvas, $x, $y, $radius * 2 - 40, $radius * 2 - 40, $fill);
        }

        [$tr, $tg, $tb] = sscanf($foreground, '#%02x%02x%02x');
        $font = public_path('fonts/Poppins-Bold.ttf');
        $size = strlen($text) > 3 ? 190 : 250;
        $box = imagettfbbox($size, 0, $font, $text);
        $x = (int) (($big - ($box[2] - $box[0])) / 2 - $box[0]);
        $y = (int) (($big - ($box[1] - $box[7])) / 2 - $box[7]);
        imagettftext($canvas, $size, 0, $x, $y, imagecolorallocate($canvas, $tr, $tg, $tb), $font, $text);

        $small = imagecreatetruecolor(400, 400);
        imagealphablending($small, false);
        imagesavealpha($small, true);
        imagefill($small, 0, 0, imagecolorallocatealpha($small, 0, 0, 0, 127));
        imagecopyresampled($small, $canvas, 0, 0, 0, 0, 400, 400, $big, $big);

        ob_start();
        imagepng($small);
        $png = (string) ob_get_clean();

        Storage::disk(config('video.disk'))->put($project->assetsPath().'/'.$asset, $png);
    }
}
