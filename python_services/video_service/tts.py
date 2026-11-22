"""Text to speech using Microsoft Edge neural voices (needs an internet connection)."""

import asyncio
import subprocess
import tempfile
from pathlib import Path

import edge_tts
import imageio_ffmpeg
import numpy as np

from .audio import SAMPLE_RATE


class VoiceSettings:
    def __init__(self, voice: str, rate: int = 0, pitch: int = 0, volume: int = 0) -> None:
        self.voice = voice
        self.rate = f"{rate:+d}%"
        self.pitch = f"{pitch:+d}Hz"
        self.volume = f"{volume:+d}%"


# The "Multilingual" HD voices are sometimes refused by the speech service ("No audio was received").
# When that happens the closest classic voice speaks instead, so a video is never silent.
FALLBACK_VOICES = {
    "en-US-AndrewMultilingualNeural": "en-US-GuyNeural",
    "en-US-BrianMultilingualNeural": "en-US-GuyNeural",
    "en-US-AvaMultilingualNeural": "en-US-AriaNeural",
    "en-US-EmmaMultilingualNeural": "en-US-JennyNeural",
}


# A voice that failed once is skipped for the rest of this run, so one broken HD voice
# does not slow down every line of a video.
_broken_voices: set[str] = set()


async def _synthesize(text: str, settings: VoiceSettings, destination: Path) -> None:
    voices = [settings.voice]
    if settings.voice in FALLBACK_VOICES:
        voices.append(FALLBACK_VOICES[settings.voice])
    voices = [voice for voice in voices if voice not in _broken_voices] or voices[-1:]

    for position, voice in enumerate(voices):
        last = position == len(voices) - 1
        communicate = edge_tts.Communicate(
            text,
            voice,
            rate=settings.rate,
            pitch=settings.pitch,
            volume=settings.volume,
        )
        try:
            # A voice with a fallback only gets a short time to answer.
            await asyncio.wait_for(communicate.save(str(destination)), timeout=60 if last else 12)
            return
        except (edge_tts.exceptions.NoAudioReceived, asyncio.TimeoutError):
            if last:
                raise
            _broken_voices.add(voice)


def speech_mp3(text: str, settings: VoiceSettings) -> bytes:
    """Return an MP3 for the text (used for the voice preview button)."""
    with tempfile.TemporaryDirectory() as folder:
        destination = Path(folder) / "speech.mp3"
        asyncio.run(_synthesize(text, settings, destination))
        return destination.read_bytes()


def speech_samples(text: str, settings: VoiceSettings) -> np.ndarray:
    """Return the spoken text decoded to mono float32 samples at 44.1 kHz."""
    with tempfile.TemporaryDirectory() as folder:
        destination = Path(folder) / "speech.mp3"
        asyncio.run(_synthesize(text, settings, destination))

        decoded = subprocess.run(
            [
                imageio_ffmpeg.get_ffmpeg_exe(),
                "-loglevel", "error",
                "-i", str(destination),
                "-ac", "1",
                "-ar", str(SAMPLE_RATE),
                "-f", "s16le",
                "-",
            ],
            capture_output=True,
            check=True,
        )

    return np.frombuffer(decoded.stdout, dtype=np.int16).astype(np.float32) / 32768.0
