<?php

namespace App\Http\Controllers;

use App\Enums\ProjectType;
use App\Enums\VideoStatus;
use App\Http\Requests\IndexProjectRequest;
use App\Http\Requests\StoreProjectAssetRequest;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Models\VideoSetting;
use App\Services\ProjectTemplates;
use App\Services\ProjectUploadMetadata;
use App\Services\PythonVideoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectController extends Controller
{
    public function index(IndexProjectRequest $request): View
    {
        $filters = $request->safe()->only(['q', 'type', 'category', 'language', 'tag', 'status']);

        return view('projects.index', [
            'projects' => Project::query()->filter($filters)->latest('updated_at')->latest('id')->get(),
            'templates' => ProjectTemplates::options(),
            'filters' => $filters,
            'total' => Project::count(),
            'typeCounts' => Project::query()->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type'),
            'tagCounts' => array_slice(Project::tagCounts(), 0, 14, true),
            'categories' => config('studio.categories'),
        ]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $template = $request->validated('template');

        $project = Project::create([
            'name' => $request->validated('name') ?: ProjectTemplates::options()[$template]['label'],
            'type' => ProjectTemplates::type($template),
            'document' => ProjectTemplates::document($template),
        ]);

        return to_route('projects.edit', $project);
    }

    public function edit(Project $project): View
    {
        return view('projects.edit', [
            'project' => $project,
            'voices' => collect(config('video.voices'))->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
            'types' => collect(ProjectType::cases())->map(fn (ProjectType $type) => ['key' => $type->value, 'label' => $type->label()]),
            'categories' => config('studio.categories'),
        ]);
    }

    /**
     * Auto-save from the editor.
     */
    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $details = $request->safe()->only(['name', 'type', 'category', 'language', 'tags', 'upload_title', 'upload_description', 'hashtags']);

        // The rules only check the important fields. The whole document is kept, because the
        // editor and the renderer need the rest too (position, size, colours, language and so on).
        $details['document'] = $request->input('document');

        if (isset($details['tags'])) {
            $details['tags'] = collect($details['tags'])
                ->map(fn ($tag) => Str::lower(trim((string) $tag)))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        if (isset($details['hashtags'])) {
            $details['hashtags'] = collect($details['hashtags'])
                ->map(fn ($tag) => ltrim(trim((string) $tag), '#'))
                ->map(fn (string $tag) => preg_replace('/\s+/', '', $tag))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        $project->update($details);

        return response()->json(['saved' => true, 'updated_at' => $project->updated_at->toIso8601String()]);
    }

    public function duplicate(Project $project): RedirectResponse
    {
        $copy = $project->replicate(['render_status', 'render_progress', 'render_message', 'video_path', 'duration_seconds']);
        $copy->name = Str::limit($project->name, 70, '').' copy';
        $copy->save();

        // The copy needs its own pictures, because deleting one project removes its folder.
        $disk = Storage::disk(config('video.disk'));
        foreach ($disk->files($project->assetsPath()) as $file) {
            $disk->copy($file, $copy->assetsPath().'/'.basename($file));
        }

        return to_route('projects.edit', $copy)->with('status', 'Copied. You are editing the copy.');
    }

    /**
     * Suggest an upload title, description and hashtags. Nothing is saved until the editor saves.
     */
    public function suggestUpload(Project $project, ProjectUploadMetadata $metadata): JsonResponse
    {
        return response()->json($metadata->suggest($project));
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->deleteWithFile();

        return to_route('projects.index')->with('status', 'Project deleted.');
    }

    /**
     * Save an uploaded picture (PNG with see-through parts is fine) for use in this project.
     */
    public function storeAsset(StoreProjectAssetRequest $request, Project $project): JsonResponse
    {
        $file = $request->file('image');
        $name = Str::random(20).'.'.$file->extension();

        Storage::disk(config('video.disk'))->putFileAs($project->assetsPath(), $file, $name);
        [$width, $height] = getimagesize($file->getRealPath()) ?: [800, 800];

        return response()->json([
            'asset' => $name,
            'width' => $width,
            'height' => $height,
        ], 201);
    }

    public function showAsset(Project $project, string $asset): BinaryFileResponse
    {
        $path = $project->assetsPath().'/'.$asset;

        abort_unless(Storage::disk(config('video.disk'))->exists($path), 404);

        return response()->file(Storage::disk(config('video.disk'))->path($path));
    }

    /**
     * Ask Python to render the saved project to an MP4.
     */
    public function render(Project $project, PythonVideoService $python): JsonResponse
    {
        if ($project->render_status === VideoStatus::Rendering || $project->render_status === VideoStatus::Pending) {
            return response()->json(['message' => 'This project is already rendering.'], 409);
        }

        $project->update([
            'render_status' => VideoStatus::Pending,
            'render_progress' => 0,
            'render_message' => 'Waiting for the render to start',
        ]);

        try {
            $python->startProjectRender($project, VideoSetting::current()->only(array_keys(config('video.defaults'))));
        } catch (RuntimeException $exception) {
            $project->update(['render_status' => VideoStatus::Failed, 'render_message' => $exception->getMessage()]);

            return response()->json(['message' => $exception->getMessage()], 503);
        }

        return response()->json($this->statusPayload($project), 202);
    }

    public function status(Project $project): JsonResponse
    {
        return response()->json($this->statusPayload($project));
    }

    /**
     * Stream the rendered MP4 inline (supports seeking in the player).
     */
    public function file(Project $project): BinaryFileResponse
    {
        abort_unless($project->isPlayable(), 404);

        return response()->file(Storage::disk(config('video.disk'))->path($project->video_path), ['Content-Type' => 'video/mp4']);
    }

    public function download(Project $project): StreamedResponse
    {
        abort_unless($project->isPlayable(), 404);

        return Storage::disk(config('video.disk'))->download($project->video_path, Str::slug($project->name ?: 'video').'-'.$project->id.'.mp4');
    }

    /**
     * @return array<string, mixed>
     */
    private function statusPayload(Project $project): array
    {
        return [
            'status' => $project->render_status?->value,
            'label' => $project->render_status?->label(),
            'progress' => $project->render_progress,
            'message' => $project->render_message,
            'finished' => $project->render_status?->isFinished() ?? false,
            'playable' => $project->isPlayable(),
            'file_url' => $project->isPlayable() ? route('projects.file', $project).'?v='.$project->updated_at->timestamp : null,
            'download_url' => $project->isPlayable() ? route('projects.download', $project) : null,
        ];
    }
}
