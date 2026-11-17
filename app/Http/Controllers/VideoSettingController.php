<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateVideoSettingRequest;
use App\Models\VideoSetting;
use App\Services\PythonVideoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use RuntimeException;

class VideoSettingController extends Controller
{
    public function edit(): View
    {
        return view('settings.edit', [
            'setting' => VideoSetting::current(),
            'voices' => config('video.voices'),
            'themes' => config('video.themes'),
            'qualities' => config('video.qualities'),
        ]);
    }

    public function update(UpdateVideoSettingRequest $request): RedirectResponse
    {
        VideoSetting::current()->update($request->validated());

        return to_route('settings.edit')->with('status', 'Video settings saved.');
    }

    /**
     * Speak a sample sentence with the voice settings currently on the form.
     */
    public function preview(UpdateVideoSettingRequest $request, PythonVideoService $python): Response|JsonResponse
    {
        try {
            $audio = $python->previewVoice($request->validated());
        } catch (ConnectionException) {
            return response()->json(['message' => 'The Python service is not running.'], 503);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 502);
        }

        return response($audio, 200, ['Content-Type' => 'audio/mpeg']);
    }
}
