"""Builds the timeline (question, think, reveal, explain, outro) and encodes the MP4."""

from __future__ import annotations

import subprocess
import tempfile
from collections.abc import Callable
from pathlib import Path

import imageio_ffmpeg
import numpy as np

from . import audio, config
from .frames import HEIGHT, WIDTH, FrameBuilder
from .hooks import pick_hook
from .markdown import plain_text
from .music import background
from .themes import get_theme
from .tts import VoiceSettings, speech_samples

ProgressCallback = Callable[[int, str], None]

OUTRO_SECONDS = 1.5
REVEAL_DELAY = 0.3
HOOK_SECONDS = 2.6
START_DELAY = 0.3
MUSIC_GAIN = 0.2
MIN_TOTAL_SECONDS = 22.0

# Lower CRF = sharper picture and a bigger file. Slower presets compress better.
QUALITY_PRESETS = {
    "standard": {"crf": "23", "preset": "veryfast", "audio": "128k"},
    "high": {"crf": "17", "preset": "medium", "audio": "192k"},
    "max": {"crf": "12", "preset": "slow", "audio": "256k"},
}


def estimate_speech_seconds(text: str) -> float:
    return 0.6 + 0.4 * len(text.split())


def narration_script(question: dict, settings: dict) -> dict[str, str | None]:
    """The sentences the voice reads. None means that part stays silent.

    The options and the explanation are only shown on screen, never spoken:
    the voice asks the question, waits while the timer runs, then names the right letter.
    """
    letter = chr(65 + int(question["correct_option"]))
    hook = f"{pick_hook(question)} " if settings.get("hook_line", True) else ""

    return {
        "question": f"{hook}{question['question_text']} Choose the correct answer." if settings["narrate_question"] else None,
        "think": None,
        "reveal": f"Correct answer is option {letter}." if settings["narrate_explanation"] else None,
        "explain": None,
    }


class Narration:
    """Spoken clips, or silent estimates when text to speech is unavailable."""

    def __init__(self, script: dict[str, str | None], settings: dict, report: ProgressCallback) -> None:
        self.script = script
        self.samples: dict[str, np.ndarray | None] = {}
        self.error: str | None = None

        voice = VoiceSettings(
            settings["voice"], settings["speech_rate"], settings["speech_pitch"], settings["speech_volume"]
        )
        # The answer is read by a second voice so it stands out from the question.
        answer_voice = VoiceSettings(
            settings.get("answer_voice") or settings["voice"],
            settings["speech_rate"], settings["speech_pitch"], settings["speech_volume"],
        )
        texts = [(name, text) for name, text in script.items() if text]

        for position, (name, text) in enumerate(texts):
            report(5 + int(20 * position / max(1, len(texts))), f"Recording voice ({position + 1}/{len(texts)})")
            if self.error is not None:
                self.samples[name] = None
                continue
            try:
                self.samples[name] = speech_samples(text, answer_voice if name == "reveal" else voice)
            except Exception as exc:  # network or voice problems must not stop the video
                self.error = f"{type(exc).__name__}: {exc}"[:160]
                self.samples[name] = None

    def seconds(self, name: str) -> float:
        samples = self.samples.get(name)
        if samples is not None:
            return len(samples) / audio.SAMPLE_RATE
        text = self.script.get(name)
        return estimate_speech_seconds(text) if text else 0.0


def build_timeline(narration: Narration, settings: dict, question: dict) -> list[tuple[str, float]]:
    """Think, reveal, explain, outro. The timer starts at once and the question is spoken while it runs."""
    explanation_words = len(plain_text(question["explanation"]).split())
    reading_time = min(14.0, 1.5 + 0.3 * explanation_words)

    spoken = narration.seconds("question")
    think_seconds = float(settings["think_seconds"])
    if spoken > 0:
        think_seconds = max(think_seconds, START_DELAY + spoken + 3.0)

    reveal_seconds = max(2.5, narration.seconds("reveal") + 1.0)
    explain_seconds = max(4.0, reading_time)

    # Keep videos from being too short: spend the missing time on the explanation.
    shortfall = MIN_TOTAL_SECONDS - (think_seconds + reveal_seconds + explain_seconds + OUTRO_SECONDS)
    if shortfall > 0:
        explain_seconds += min(shortfall, 8.0)

    return [
        ("think", think_seconds),
        ("reveal", reveal_seconds),
        ("explain", explain_seconds),
        ("outro", OUTRO_SECONDS),
    ]


def build_audio(narration: Narration, timeline: list[tuple[str, float]], settings: dict, total_seconds: float) -> np.ndarray:
    length = int(round(total_seconds * audio.SAMPLE_RATE))
    track = np.zeros(length, dtype=np.float32)  # sound effects
    voice = np.zeros(length, dtype=np.float32)  # speech, kept apart so the music can duck under it
    effects = settings.get("sound_effects", True)

    start = 0.0
    for name, duration in timeline:
        if name == "think":
            if effects:
                audio.mix_in(track, audio.whoosh(), start)
            _place(voice, narration.samples.get("question"), start + START_DELAY)

            spoken_until = START_DELAY + narration.seconds("question")
            if settings["tick_sound"]:
                for second in range(int(duration)):
                    if second < spoken_until:
                        continue
                    last_seconds = second >= int(duration) - 3
                    audio.mix_in(track, audio.tick(high=last_seconds), start + second, gain=0.8)
            if effects and duration - 3.0 >= spoken_until:
                audio.mix_in(track, audio.drumroll(3.0), start + duration - 3.0, gain=0.55)
        elif name == "reveal":
            if effects:
                audio.mix_in(track, audio.buzzer(), start, gain=0.9)
                audio.mix_in(track, audio.pop(), start + REVEAL_DELAY, gain=0.8)
            audio.mix_in(track, audio.ding(), start + (REVEAL_DELAY if effects else 0.0), gain=1.0)
            _place(voice, narration.samples.get("reveal"), start + (0.55 if effects else 0.35))
        start += duration

    mixed = track + voice
    if settings.get("background_music", True):
        mixed += music_under(voice, length)
    return mixed


def music_under(voice: np.ndarray, length: int) -> np.ndarray:
    """The background loop, turned down while someone is speaking."""
    window = int(0.15 * audio.SAMPLE_RATE)
    padded = np.concatenate(([0.0], np.cumsum(np.abs(voice), dtype=np.float64)))
    average = (padded[window:] - padded[:-window]) / window
    envelope = np.concatenate((average, np.zeros(length - len(average)))) if len(average) < length else average[:length]
    ducking = 1.0 - 0.7 * np.clip(envelope * 8.0, 0.0, 1.0)
    return (background(length / audio.SAMPLE_RATE) * ducking * MUSIC_GAIN).astype(np.float32)


def _place(track: np.ndarray, samples: np.ndarray | None, start: float) -> None:
    if samples is not None:
        audio.mix_in(track, samples, start)


def frame_for(builder: FrameBuilder, scene: str, elapsed: float, duration: float):
    """Return (cache key, draw function). Equal keys produce identical frames."""
    if scene == "think":
        remaining = max(0.0, duration - int(elapsed * 10) / 10)
        return ("think", int(remaining * 10)), lambda: builder.think_scene(remaining, duration - remaining)
    if scene == "reveal":
        step = min(max(0, int((elapsed - REVEAL_DELAY) * 24)), 48)
        return ("reveal", step), lambda: builder.reveal_scene(step / 24)
    if scene == "explain":
        progress = min(1.0, elapsed / max(0.5, duration * 0.8))
        step = int(progress * 240)
        return ("explain", step), lambda: builder.explain_scene(step / 240)
    return ("outro",), builder.outro_scene


def resolve_output(output_path: str) -> Path:
    """Only allow files below the shared Laravel storage folder."""
    root = config.LARAVEL_STORAGE_PATH.resolve()
    destination = (root / output_path).resolve()
    if root not in destination.parents or destination.suffix != ".mp4":
        raise ValueError("Invalid output path")
    return destination


def render_video(question: dict, settings: dict, output_path: str, report: ProgressCallback) -> tuple[float, str | None]:
    """Render the video and return (duration seconds, warning message or None)."""
    destination = resolve_output(output_path)
    destination.parent.mkdir(parents=True, exist_ok=True)
    partial = destination.with_name(destination.stem + ".partial.mp4")

    report(3, "Preparing")
    narration = Narration(narration_script(question, settings), settings, report)
    timeline = build_timeline(narration, settings, question)

    fps = config.FPS
    frame_counts = [max(1, round(duration * fps)) for _, duration in timeline]
    total_frames = sum(frame_counts)
    total_seconds = total_frames / fps

    think_duration = next(duration for name, duration in timeline if name == "think")
    builder = FrameBuilder(
        get_theme(settings["theme"]), question, settings.get("channel_name"), think_duration,
        comment_prompt=bool(settings.get("comment_prompt", True)),
        hook_text=pick_hook(question) if settings.get("hook_line", True) else None,
        mascot=bool(settings.get("mascot", True)),
        reveal_effects=bool(settings.get("reveal_effects", True)),
    )

    with tempfile.TemporaryDirectory() as folder:
        wav_path = Path(folder) / "audio.wav"
        log_path = Path(folder) / "ffmpeg.log"
        audio.write_wav(wav_path, build_audio(narration, timeline, settings, total_seconds))

        quality = QUALITY_PRESETS.get(settings.get("quality", "high"), QUALITY_PRESETS["high"])
        command = [
            imageio_ffmpeg.get_ffmpeg_exe(), "-y", "-loglevel", "error",
            "-f", "rawvideo", "-pix_fmt", "rgb24", "-s", f"{WIDTH}x{HEIGHT}", "-r", str(fps), "-i", "-",
            "-i", str(wav_path),
            "-c:v", "libx264", "-preset", quality["preset"], "-crf", quality["crf"], "-pix_fmt", "yuv420p",
            "-c:a", "aac", "-b:a", quality["audio"], "-shortest", "-movflags", "+faststart",
            str(partial),
        ]

        with open(log_path, "wb") as log_file:
            process = subprocess.Popen(command, stdin=subprocess.PIPE, stderr=log_file)
            try:
                _write_frames(process, builder, timeline, frame_counts, fps, total_frames, report)
            except BrokenPipeError:
                pass
            finally:
                if process.stdin:
                    try:
                        process.stdin.close()
                    except BrokenPipeError:
                        pass
                process.wait()

        if process.returncode != 0:
            partial.unlink(missing_ok=True)
            details = log_path.read_text(errors="replace")[-300:]
            raise RuntimeError(f"ffmpeg failed: {details}")

    partial.replace(destination)
    warning = f"Video is ready, but the voice was unavailable ({narration.error})" if narration.error else None
    return total_seconds, warning


def _write_frames(process, builder, timeline, frame_counts, fps, total_frames, report) -> None:
    last_key = None
    last_bytes = b""
    written = 0
    last_progress = -1

    for (scene, duration), count in zip(timeline, frame_counts):
        for index in range(count):
            key, draw = frame_for(builder, scene, index / fps, duration)
            if key != last_key:
                last_bytes = draw().tobytes()
                last_key = key
            process.stdin.write(last_bytes)
            written += 1

            progress = 30 + int(65 * written / total_frames)
            if progress != last_progress and progress % 5 == 0:
                last_progress = progress
                report(progress, "Drawing and encoding video")
