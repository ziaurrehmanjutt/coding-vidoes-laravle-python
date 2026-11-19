<?php

namespace App\Models;

use App\Enums\VideoStatus;
use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'title',
    'language',
    'hook',
    'category_id',
    'series',
    'series_number',
    'question_text',
    'code',
    'options',
    'correct_option',
    'explanation',
    'think_seconds',
    'uploaded_at',
    'upload_url',
    'upload_title',
    'upload_description',
    'hashtags',
])]
class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'language' => 'python',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'options' => 'array',
            'correct_option' => 'integer',
            'think_seconds' => 'integer',
            'uploaded_at' => 'datetime',
            'series_number' => 'integer',
            'hashtags' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<GeneratedVideo, $this>
     */
    public function videos(): HasMany
    {
        return $this->hasMany(GeneratedVideo::class);
    }

    /**
     * The newest video that finished rendering.
     *
     * @return HasOne<GeneratedVideo, $this>
     */
    public function completedVideo(): HasOne
    {
        return $this->hasOne(GeneratedVideo::class)
            ->ofMany(['id' => 'max'], fn (Builder $query) => $query->where('status', VideoStatus::Completed));
    }

    /**
     * @return HasOne<GeneratedVideo, $this>
     */
    public function latestVideo(): HasOne
    {
        return $this->hasOne(GeneratedVideo::class)->latestOfMany();
    }

    /**
     * Apply the search and filter choices from the questions list.
     *
     * @param  Builder<Question>  $query
     * @param  array{q?: ?string, language?: ?string, category?: ?string, series?: ?string, uploaded?: ?string, video?: ?string}  $filters
     */
    #[Scope]
    protected function filter(Builder $query, array $filters): void
    {
        $query
            ->when($filters['q'] ?? null, function (Builder $query, string $term) {
                $query->where(function (Builder $query) use ($term) {
                    $like = '%'.$term.'%';

                    $query->where('title', 'like', $like)
                        ->orWhere('question_text', 'like', $like)
                        ->orWhere('code', 'like', $like)
                        ->orWhere('explanation', 'like', $like);
                });
            })
            ->when($filters['language'] ?? null, fn (Builder $query, string $language) => $query->where('language', $language))
            ->when($filters['category'] ?? null, fn (Builder $query, string $category) => $category === 'none'
                ? $query->whereNull('category_id')
                : $query->where('category_id', $category))
            ->when($filters['series'] ?? null, fn (Builder $query, string $series) => $series === 'none'
                ? $query->whereNull('series')
                : $query->where('series', $series))
            ->when($filters['uploaded'] ?? null, fn (Builder $query, string $uploaded) => $uploaded === 'yes'
                ? $query->whereNotNull('uploaded_at')
                : $query->whereNull('uploaded_at'))
            ->when($filters['video'] ?? null, fn (Builder $query, string $video) => $video === 'with'
                ? $query->whereHas('videos', fn (Builder $query) => $query->where('status', VideoStatus::Completed))
                : $query->whereDoesntHave('videos', fn (Builder $query) => $query->where('status', VideoStatus::Completed)));
    }

    /**
     * Order for posting: series in the configured order, then by number, unnumbered last.
     *
     * @param  Builder<Question>  $query
     */
    #[Scope]
    protected function inSequence(Builder $query): void
    {
        $series = array_keys(config('video.series'));

        $cases = collect($series)->map(fn () => 'when ? then ?')->implode(' ');
        $bindings = collect($series)->flatMap(fn (string $key, int $position) => [$key, $position])->all();

        $query->orderByRaw("case series {$cases} else ? end", [...$bindings, count($series)])
            ->orderByRaw('series_number is null')
            ->orderBy('series_number')
            ->orderBy('id');
    }

    /**
     * The next free number in a series.
     */
    public static function nextSeriesNumber(string $series): int
    {
        return (int) static::query()->where('series', $series)->max('series_number') + 1;
    }

    /**
     * "Day 3", "Challenge 12", or null when the question is not in a series.
     */
    public function seriesLabel(): ?string
    {
        if ($this->series === null || $this->series_number === null) {
            return null;
        }

        return config('video.series.'.$this->series, ucfirst($this->series)).' '.$this->series_number;
    }

    /**
     * The label of the following number, shown at the end of the video.
     */
    public function nextSeriesLabel(): ?string
    {
        if ($this->seriesLabel() === null) {
            return null;
        }

        return config('video.series.'.$this->series, ucfirst($this->series)).' '.($this->series_number + 1);
    }

    /**
     * Hashtags as they are written in a post: "#python #quiz".
     */
    public function hashtagText(): string
    {
        return collect($this->hashtags ?? [])->map(fn (string $tag) => '#'.$tag)->implode(' ');
    }

    public function isUploaded(): bool
    {
        return $this->uploaded_at !== null;
    }

    public function languageLabel(): string
    {
        return config('video.languages.'.$this->language, ucfirst($this->language));
    }

    public function correctLetter(): string
    {
        return chr(ord('A') + $this->correct_option);
    }

    public function correctAnswerText(): string
    {
        return (string) ($this->options[$this->correct_option] ?? '');
    }
}
