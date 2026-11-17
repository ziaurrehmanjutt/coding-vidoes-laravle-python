<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Question;
use App\Services\UploadMetadata;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    /**
     * Seed example questions in several languages. Every correct answer was
     * checked by running its code, and existing questions are never overwritten.
     *
     * Python questions become "Day 1, Day 2..." and the others "Challenge 1, Challenge 2...".
     * Upload titles, descriptions and hashtags are generated for questions that have none.
     */
    public function run(): void
    {
        $metadata = app(UploadMetadata::class);
        $counters = [];

        foreach ($this->questions() as $data) {
            $category = Category::query()->firstOrCreate(['name' => $data['category']]);
            unset($data['category']);

            $series = $data['language'] === 'python' ? 'day' : 'challenge';
            $counters[$series] = ($counters[$series] ?? 0) + 1;

            $question = Question::query()->firstOrCreate(
                ['title' => $data['title']],
                ['question_text' => 'What will be the correct output?', 'category_id' => $category->id] + $data,
            );

            $question->category_id ??= $category->id;

            $numberIsFree = ! Question::query()
                ->where('series', $series)
                ->where('series_number', $counters[$series])
                ->exists();

            if ($question->series === null && $numberIsFree) {
                $question->series = $series;
                $question->series_number = $counters[$series];
            }

            foreach ($metadata->suggest($question) as $field => $value) {
                if (blank($question->{$field})) {
                    $question->{$field} = $value;
                }
            }

            $question->save();
        }
    }

    /**
     * @return list<array{language: string, category: string, title: string, code: string, options: list<string>, correct_option: int, explanation: string}>
     */
    private function questions(): array
    {
        return [
            [
                'language' => 'python',
                'category' => 'Strings',
                'title' => 'Python string slicing',
                'code' => implode("\n", ['s = "Python"', '', 'print(s[1:5] + s[-2:])']),
                'options' => ['ython', 'ythoon', 'Pythoon', 'ythonon'],
                'correct_option' => 1,
                'explanation' => 's[1:5] takes the characters at index 1 to 4, which is "ytho". s[-2:] takes the last two characters, which is "on". Joining them gives "ythoon".',
            ],
            [
                'language' => 'python',
                'category' => 'Lists & Arrays',
                'title' => 'List slicing',
                'code' => implode("\n", ['nums = [1, 2, 3, 4, 5]', 'print(nums[1:4])']),
                'options' => ['[1, 2, 3, 4]', '[2, 3, 4]', '[2, 3, 4, 5]', '[1, 2, 3]'],
                'correct_option' => 1,
                'explanation' => 'nums[1:4] starts at index 1 and stops before index 4. So it takes the items at index 1, 2 and 3, which are 2, 3 and 4.',
            ],
            [
                'language' => 'python',
                'category' => 'Operators & Math',
                'title' => 'Floor division and modulo',
                'code' => implode("\n", ['print(7 // 2, 7 % 2)']),
                'options' => ['3.5 1', '3 1', '4 1', '3 0'],
                'correct_option' => 1,
                'explanation' => 'Double slash is floor division, so 7 // 2 is 3. Percent gives the remainder, so 7 % 2 is 1. Print shows both separated by a space.',
            ],
            [
                'language' => 'python',
                'category' => 'Strings',
                'title' => 'String repetition',
                'code' => implode("\n", ['print("ab" * 3 + "c")']),
                'options' => ['ababc', 'abababc', 'ab3c', 'abcabcabc'],
                'correct_option' => 1,
                'explanation' => 'Multiplying a string repeats it. "ab" times 3 is "ababab". Then plus "c" joins one more letter, giving "abababc".',
            ],
            [
                'language' => 'python',
                'category' => 'Lists & Arrays',
                'title' => 'Two names, one list',
                'code' => implode("\n", ['a = [1, 2]', 'b = a', 'b.append(3)', 'print(a)']),
                'options' => ['[1, 2]', '[1, 2, 3]', '[3]', 'Error'],
                'correct_option' => 1,
                'explanation' => 'b = a does not copy the list. Both names point to the same list. So appending to b also changes a, and a prints as 1, 2, 3.',
            ],
            [
                'language' => 'python',
                'category' => 'Dictionaries & Sets',
                'title' => 'Dictionary get',
                'code' => implode("\n", ['d = {"a": 1, "b": 2}', 'print(d.get("c", 0) + d["b"])']),
                'options' => ['0', '1', '2', 'KeyError'],
                'correct_option' => 2,
                'explanation' => 'd.get("c", 0) returns the default 0 because the key c does not exist. Then d["b"] is 2. So 0 plus 2 is 2.',
            ],
            [
                'language' => 'python',
                'category' => 'Loops & Comprehensions',
                'title' => 'Sum of a range',
                'code' => implode("\n", ['print(sum(range(1, 5)))']),
                'options' => ['15', '10', '9', '14'],
                'correct_option' => 1,
                'explanation' => 'range(1, 5) gives 1, 2, 3 and 4, because the end value is not included. Their sum is 10.',
            ],
            [
                'language' => 'python',
                'category' => 'Functions',
                'title' => 'Mutable default argument',
                'code' => implode("\n", ['def add(x, items=[]):', '    items.append(x)', '    return items', '', 'add(1)', 'print(add(2))']),
                'options' => ['[2]', '[1, 2]', '[1]', 'Error'],
                'correct_option' => 1,
                'explanation' => 'The default list is created only once, when the function is defined. The first call adds 1 to it. The second call adds 2 to the same list, so it prints 1, 2.',
            ],
            [
                'language' => 'python',
                'category' => 'Strings',
                'title' => 'Title and replace',
                'code' => implode("\n", ['print("hello world".title().replace(" ", "_"))']),
                'options' => ['hello_world', 'Hello_World', 'Hello World', 'HELLO_WORLD'],
                'correct_option' => 1,
                'explanation' => 'title() capitalizes each word, giving "Hello World". Then replace changes the space into an underscore, giving "Hello_World".',
            ],
            [
                'language' => 'python',
                'category' => 'Operators & Math',
                'title' => 'Truthy or',
                'code' => implode("\n", ['print(0 or "" or "py")']),
                'options' => ['0', 'py', 'True', 'None'],
                'correct_option' => 1,
                'explanation' => 'The or operator returns the first truthy value. Zero and the empty string are both falsy, so it moves on and returns "py".',
            ],
            [
                'language' => 'python',
                'category' => 'Types & Variables',
                'title' => 'Swap values',
                'code' => implode("\n", ['a, b = 1, 2', 'a, b = b, a + b', 'print(a, b)']),
                'options' => ['2 3', '2 2', '1 3', '3 2'],
                'correct_option' => 0,
                'explanation' => 'The right side is worked out first using the old values: b is 2, and a plus b is 3. Then a becomes 2 and b becomes 3.',
            ],
            [
                'language' => 'python',
                'category' => 'Loops & Comprehensions',
                'title' => 'List comprehension',
                'code' => implode("\n", ['print([x * x for x in range(5) if x % 2 == 0])']),
                'options' => ['[0, 4, 16]', '[0, 1, 4, 9, 16]', '[4, 16]', '[1, 9]'],
                'correct_option' => 0,
                'explanation' => 'x runs from 0 to 4, and the if keeps only even numbers: 0, 2 and 4. Squaring them gives 0, 4 and 16.',
            ],
            [
                'language' => 'python',
                'category' => 'Types & Variables',
                'title' => 'Type of a division',
                'code' => implode("\n", ['print(type(3 / 1).__name__)']),
                'options' => ['int', 'float', 'number', 'Error'],
                'correct_option' => 1,
                'explanation' => 'A single slash always does true division, and the result is a float even when it divides evenly. So the type name is float.',
            ],
            [
                'language' => 'python',
                'category' => 'Strings',
                'title' => 'Negative index',
                'code' => implode("\n", ['s = "Python"', 'print(s[-1] + s[0])']),
                'options' => ['nP', 'Pn', 'nn', 'Py'],
                'correct_option' => 0,
                'explanation' => 's[-1] is the last character, n. s[0] is the first character, P. Joining them gives nP.',
            ],
            [
                'language' => 'python',
                'category' => 'Dictionaries & Sets',
                'title' => 'Set removes duplicates',
                'code' => implode("\n", ['print(len({1, 2, 2, 3, 3, 3}))']),
                'options' => ['6', '3', '4', '2'],
                'correct_option' => 1,
                'explanation' => 'A set keeps only unique values. The duplicates collapse, leaving 1, 2 and 3. So the length is 3.',
            ],
            [
                'language' => 'javascript',
                'category' => 'Types & Variables',
                'title' => 'JS typeof null',
                'code' => implode("\n", ['console.log(typeof null)']),
                'options' => ['null', 'object', 'undefined', 'number'],
                'correct_option' => 1,
                'explanation' => 'This is a famous quirk. typeof null returns the string object, because of an old bug in the language that was never fixed.',
            ],
            [
                'language' => 'javascript',
                'category' => 'Lists & Arrays',
                'title' => 'JS map and join',
                'code' => implode("\n", ['console.log([1, 2, 3].map(n => n * 2).join("-"))']),
                'options' => ['1-2-3', '2-4-6', '2,4,6', '246'],
                'correct_option' => 1,
                'explanation' => 'map doubles every item, giving 2, 4 and 6. Then join glues them together using a dash, so the result is 2-4-6.',
            ],
            [
                'language' => 'javascript',
                'category' => 'Operators & Math',
                'title' => 'JS plus and minus with strings',
                'code' => implode("\n", ['console.log("5" + 3, "5" - 3)']),
                'options' => ['8 2', '53 2', '53 53', '8 8'],
                'correct_option' => 1,
                'explanation' => 'Plus with a string joins text, so "5" + 3 is "53". Minus only works on numbers, so "5" becomes 5 and 5 - 3 is 2.',
            ],
            [
                'language' => 'javascript',
                'category' => 'Operators & Math',
                'title' => 'JS floating point',
                'code' => implode("\n", ['console.log(0.1 + 0.2 === 0.3)']),
                'options' => ['true', 'false', 'undefined', 'NaN'],
                'correct_option' => 1,
                'explanation' => 'Decimal numbers are stored in binary, so 0.1 + 0.2 is really 0.30000000000000004. That is not exactly 0.3, so the comparison is false.',
            ],
            [
                'language' => 'javascript',
                'category' => 'Lists & Arrays',
                'title' => 'JS array reference',
                'code' => implode("\n", ['const a = [1, 2, 3];', 'const b = a;', 'b.push(4);', 'console.log(a.length)']),
                'options' => ['3', '4', 'undefined', 'Error'],
                'correct_option' => 1,
                'explanation' => 'const stops you from reassigning b, but both names still point to the same array. Pushing to b changes it for a too, so the length is 4.',
            ],
            [
                'language' => 'javascript',
                'category' => 'Strings',
                'title' => 'JS spread and reverse a string',
                'code' => implode("\n", ['console.log([..."abc"].reverse().join(""))']),
                'options' => ['abc', 'cba', 'bca', 'acb'],
                'correct_option' => 1,
                'explanation' => 'The spread turns the string into the letters a, b and c. reverse flips their order, and join with an empty string glues them back as cba.',
            ],
            [
                'language' => 'javascript',
                'category' => 'Types & Variables',
                'title' => 'JS block scope',
                'code' => implode("\n", ['let x = 1;', '{', '  let x = 2;', '}', 'console.log(x)']),
                'options' => ['1', '2', 'undefined', 'Error'],
                'correct_option' => 0,
                'explanation' => 'let is block scoped. The x inside the braces is a different variable, so the outer x is still 1.',
            ],
            [
                'language' => 'javascript',
                'category' => 'Lists & Arrays',
                'title' => 'JS default sort',
                'code' => implode("\n", ['console.log([10, 9, 1].sort())']),
                'options' => ['[ 1, 9, 10 ]', '[ 1, 10, 9 ]', '[ 10, 9, 1 ]', '[ 9, 10, 1 ]'],
                'correct_option' => 1,
                'explanation' => 'By default, sort compares items as text. As text, 1 comes before 10, and 10 comes before 9. So the result is 1, 10, 9.',
            ],
            [
                'language' => 'php',
                'category' => 'Operators & Math',
                'title' => 'PHP numeric string',
                'code' => implode("\n", ['echo "10" + 5;']),
                'options' => ['105', '15', '10 5', 'Error'],
                'correct_option' => 1,
                'explanation' => 'In PHP, plus is only for numbers. The text "10" is converted to the number 10, so the result is 15.',
            ],
            [
                'language' => 'php',
                'category' => 'Lists & Arrays',
                'title' => 'PHP sort and implode',
                'code' => implode("\n", ['$a = [3, 1, 2];', 'sort($a);', 'echo implode(",", $a);']),
                'options' => ['3,1,2', '1,2,3', '1,3,2', '3,2,1'],
                'correct_option' => 1,
                'explanation' => 'sort puts the array in ascending order, giving 1, 2, 3. Then implode joins the items with commas.',
            ],
            [
                'language' => 'php',
                'category' => 'Strings',
                'title' => 'PHP str_repeat',
                'code' => implode("\n", ['echo str_repeat("ab", 2) . strtoupper("c");']),
                'options' => ['ababC', 'ABABC', 'abab C', 'ababc'],
                'correct_option' => 0,
                'explanation' => 'str_repeat gives "abab". strtoupper turns "c" into "C". The dot joins both pieces, so the output is ababC.',
            ],
            [
                'language' => 'php',
                'category' => 'Types & Variables',
                'title' => 'PHP loose comparison',
                'code' => implode("\n", ['var_dump(0 == "a");']),
                'options' => ['bool(true)', 'bool(false)', 'int(0)', 'Error'],
                'correct_option' => 1,
                'explanation' => 'Since PHP 8, comparing a number with a non numeric string treats the number as text. "0" is not equal to "a", so it is false.',
            ],
            [
                'language' => 'php',
                'category' => 'Types & Variables',
                'title' => 'PHP null coalescing',
                'code' => implode("\n", ['$x = null;', 'echo $x ?? "default";']),
                'options' => ['null', 'default', '0', 'Error'],
                'correct_option' => 1,
                'explanation' => 'The double question mark returns the left side if it exists and is not null. Here x is null, so it returns the text default.',
            ],
            [
                'language' => 'php',
                'category' => 'Operators & Math',
                'title' => 'PHP intdiv and modulo',
                'code' => implode("\n", ['echo intdiv(7, 2) . " " . 7 % 2;']),
                'options' => ['3.5 1', '3 1', '4 1', '3 0'],
                'correct_option' => 1,
                'explanation' => 'intdiv divides and drops the decimal part, so 7 and 2 give 3. The percent sign gives the remainder, which is 1.',
            ],
        ];
    }
}
