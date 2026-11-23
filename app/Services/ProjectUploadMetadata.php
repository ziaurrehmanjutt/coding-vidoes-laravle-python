<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Str;

/**
 * Suggests the title, description and hashtags to use when uploading a studio video.
 */
class ProjectUploadMetadata
{
    private const MAX_TITLE_LENGTH = 100;

    private const MAX_HASHTAGS = 15;

    /**
     * @return array{upload_title: string, upload_description: string, hashtags: list<string>}
     */
    public function suggest(Project $project): array
    {
        return [
            'upload_title' => Str::limit($project->name, self::MAX_TITLE_LENGTH - 3, '...'),
            'upload_description' => $this->description($project),
            'hashtags' => $this->hashtags($project),
        ];
    }

    /**
     * The first spoken line makes the best opening sentence, because it is what the video says first.
     */
    private function description(Project $project): string
    {
        $spoken = collect($project->document['objects'] ?? [])->firstWhere('type', 'voice');
        $opening = filled($spoken['text'] ?? null) ? (string) $spoken['text'] : $project->headline();
        $topic = $project->category ?? 'programming';

        return implode("\n", [
            $opening,
            '',
            "Short and visual: {$project->name}.",
            'Watch to the end, then tell me in the comments what you want explained next.',
            '',
            "Follow for more {$topic} explained in under a minute!",
        ]);
    }

    /**
     * @return list<string>
     */
    private function hashtags(Project $project): array
    {
        $tags = collect($project->tags ?? [])
            ->map(fn (string $tag) => Str::slug($tag, ''))
            ->push($project->language, 'coding', 'programming', 'shorts', 'learntocode')
            ->push($project->category !== null ? Str::slug($project->category, '') : null);

        return $tags->filter()->unique()->take(self::MAX_HASHTAGS)->values()->all();
    }
}
