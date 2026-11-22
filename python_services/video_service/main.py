"""HTTP API called by Laravel. Run with: uvicorn video_service.main:app --port 8001"""

import hmac
import logging
import threading

import imageio_ffmpeg
from fastapi import BackgroundTasks, Depends, FastAPI, Header, HTTPException, Response
from pydantic import BaseModel, Field

from . import config
from .db import update_video
from .predictor import predict_output
from .renderer import render_video, resolve_output
from .tts import VoiceSettings, speech_mp3

logger = logging.getLogger("video_service")

app = FastAPI(title="Quiz video service")

# One render at a time keeps CPU and memory predictable.
render_lock = threading.Semaphore(1)


def verify_token(x_api_token: str = Header(default="")) -> None:
    if not hmac.compare_digest(x_api_token, config.API_TOKEN):
        raise HTTPException(status_code=401, detail="Invalid API token")


class PredictRequest(BaseModel):
    code: str = Field(min_length=1, max_length=5000)
    timeout: float = Field(default=5.0, gt=0, le=10)
    language: str = Field(default="python", max_length=30)


class VoicePreviewRequest(BaseModel):
    voice: str
    speech_rate: int = Field(default=0, ge=-50, le=100)
    speech_pitch: int = Field(default=0, ge=-50, le=50)
    speech_volume: int = Field(default=0, ge=-50, le=50)
    text: str = Field(default="Can you guess the correct output?", max_length=200)


class QuestionPayload(BaseModel):
    title: str = ""
    language: str = "python"
    hook: str | None = None
    series_label: str | None = None
    next_series_label: str | None = None
    question_text: str
    code: str | None = None
    options: list[str] = Field(min_length=4, max_length=4)
    correct_option: int = Field(ge=0, le=3)
    explanation: str


class SettingsPayload(BaseModel):
    voice: str
    speech_rate: int = Field(default=0, ge=-50, le=100)
    speech_pitch: int = Field(default=0, ge=-50, le=50)
    speech_volume: int = Field(default=0, ge=-50, le=50)
    narrate_question: bool = True
    narrate_explanation: bool = True
    tick_sound: bool = True
    think_seconds: int = Field(default=8, ge=3, le=30)
    theme: str = "midnight"
    quality: str = "high"
    channel_name: str | None = None
    answer_voice: str | None = None
    comment_prompt: bool = True
    hook_line: bool = True
    mascot: bool = True
    reveal_effects: bool = True
    sound_effects: bool = True
    background_music: bool = True


class RenderRequest(BaseModel):
    video_id: int
    output_path: str
    question: QuestionPayload
    settings: SettingsPayload


@app.get("/health")
def health() -> dict:
    return {
        "status": "ok",
        "ffmpeg": imageio_ffmpeg.get_ffmpeg_exe(),
        "storage": str(config.LARAVEL_STORAGE_PATH),
        "database": str(config.DB_PATH),
    }


@app.post("/predict", dependencies=[Depends(verify_token)])
def predict(request: PredictRequest) -> dict:
    return predict_output(request.code, request.timeout, request.language)


@app.post("/tts-preview", dependencies=[Depends(verify_token)])
def tts_preview(request: VoicePreviewRequest) -> Response:
    voice = VoiceSettings(request.voice, request.speech_rate, request.speech_pitch, request.speech_volume)
    try:
        return Response(content=speech_mp3(request.text, voice), media_type="audio/mpeg")
    except Exception as exc:
        raise HTTPException(status_code=503, detail=f"Voice service unavailable: {exc}") from exc


@app.post("/render", status_code=202, dependencies=[Depends(verify_token)])
def render(request: RenderRequest, background_tasks: BackgroundTasks) -> dict:
    try:
        resolve_output(request.output_path)
    except ValueError as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc

    background_tasks.add_task(run_render, request)
    return {"accepted": True, "video_id": request.video_id}


def run_render(request: RenderRequest) -> None:
    video_id = request.video_id
    state = {"progress": 0}

    def report(progress: int, message: str) -> None:
        state["progress"] = progress
        update_video(video_id, status="rendering", progress=progress, message=message)

    with render_lock:
        try:
            report(1, "Started")
            seconds, warning = render_video(
                request.question.model_dump(), request.settings.model_dump(), request.output_path, report
            )
            update_video(
                video_id,
                status="completed",
                progress=100,
                message=warning or "Video is ready",
                path=request.output_path,
                duration_seconds=round(seconds, 2),
            )
        except Exception as exc:
            logger.exception("Render failed for video %s", video_id)
            update_video(video_id, status="failed", progress=state["progress"], message=str(exc)[:250])
