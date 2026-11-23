<?php

namespace App\Models;

use App\Enums\ProjectType;
use App\Enums\VideoStatus;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * A video made in the studio editor: objects on a timeline, saved as one JSON document.
 */
#[Fillable([
    'name',
    'type',
    'category',
    'language',
    'tags',
    'upload_title',
    'upload_description',
    'hashtags',
    'document',
    'render_status',
    'render_progress',
    'render_message',
    'video_path',
    'duration_seconds',
])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'type' => ProjectType::class,
            'tags' => 'array',
            'hashtags' => 'array',
            'document' => 'array',
            'render_status' => VideoStatus::class,
            'render_progress' => 'integer',
            'duration_seconds' => 'float',
        ];
    }

    /**
     * Search and filter for the Studio list.
     *
     * @param  Builder<Project>  $query
     * @param  array{q?: ?string, type?: ?string, category?: ?string, language?: ?string, tag?: ?string, status?: ?string}  $filters
     */
    #[Scope]
    protected function filter(Builder $query, array $filters): void
    {
        $query
            ->when($filters['q'] ?? null, function (Builder $query, string $term) {
                $like = '%'.$term.'%';

                $query->where(fn (Builder $query) => $query->where('name', 'like', $like)->orWhere('tags', 'like', $like));
            })
            ->when($filters['type'] ?? null, fn (Builder $query, string $type) => $query->where('type', $type))
            ->when($filters['category'] ?? null, fn (Builder $query, string $category) => $category === 'none'
                ? $query->whereNull('category')
                : $query->where('category', $category))
            ->when($filters['language'] ?? null, fn (Builder $query, string $language) => $query->where('language', $language))
            ->when($filters['tag'] ?? null, fn (Builder $query, string $tag) => $query->whereJsonContains('tags', $tag))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => match ($status) {
                'made' => $query->where('render_status', VideoStatus::Completed),
                'rendering' => $query->whereIn('render_status', [VideoStatus::Pending, VideoStatus::Rendering]),
                default => $query->where(fn (Builder $query) => $query->whereNull('render_status')->orWhere('render_status', VideoStatus::Failed)),
            });
    }

    /**
     * The hashtags as one line, for example "#python #coding", ready to paste into YouTube.
     */
    public function hashtagText(): string
    {
        return collect($this->hashtags ?? [])->map(fn (string $tag) => '#'.$tag)->implode(' ');
    }

    /**
     * Whether the title, description and hashtags are all filled in.
     */
    public function hasUploadDetails(): bool
    {
        return filled($this->upload_title) && filled($this->upload_description) && ! empty($this->hashtags);
    }

    /**
     * The two background colours, safe to print into a style attribute.
     *
     * @return array{0: string, 1: string}
     */
    public function backgroundColors(): array
    {
        $safe = fn (mixed $value, string $fallback) => is_string($value) && preg_match('/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $value) ? $value : $fallback;
        $top = $safe($this->document['background'] ?? null, '#0f172a');

        return [$top, $safe($this->document['background2'] ?? null, $top)];
    }

    /**
     * The first piece of text in the video, used as the card preview.
     */
    public function headline(): string
    {
        foreach ($this->document['objects'] ?? [] as $object) {
            if (($object['type'] ?? null) === 'text' && filled($object['text'] ?? null)) {
                return (string) $object['text'];
            }
        }

        return $this->name;
    }

    /**
     * Every tag in use, most common first, for the tag filter.
     *
     * @return array<string, int>
     */
    public static function tagCounts(): array
    {
        return self::query()->pluck('tags')->flatten()->filter()->countBy()->sortDesc()->all();
    }

    /**
     * Where Laravel and Python agree the rendered file lives, relative to the disk root.
     */
    public function expectedPath(): string
    {
        return config('video.directory').'/project-'.$this->getKey().'.mp4';
    }

    /**
     * The folder (relative to the disk root) that holds this project's uploaded pictures.
     */
    public function assetsPath(): string
    {
        return 'studio/'.$this->getKey();
    }

    public function isPlayable(): bool
    {
        return $this->render_status === VideoStatus::Completed
            && $this->video_path !== null
            && Storage::disk(config('video.disk'))->exists($this->video_path);
    }

    public function deleteWithFile(): void
    {
        if ($this->video_path !== null) {
            Storage::disk(config('video.disk'))->delete($this->video_path);
        }

        Storage::disk(config('video.disk'))->deleteDirectory($this->assetsPath());

        $this->delete();
    }
}
