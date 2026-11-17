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


async def _synthesize(text: str, settings: VoiceSettings, destination: Path) -> None:
    communicate = edge_tts.Communicate(
        text,
        settings.voice,
        rate=settings.rate,
        pitch=settings.pitch,
        volume=settings.volume,
    )
    await asyncio.wait_for(communicate.save(str(destination)), timeout=60)


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
