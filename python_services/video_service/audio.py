"""Sound effects and the final audio mix (mono float32 at 44.1 kHz)."""

import wave
from pathlib import Path

import numpy as np

SAMPLE_RATE = 44100


def _envelope(length: int, decay: float) -> np.ndarray:
    return np.exp(-np.linspace(0, decay, length, dtype=np.float32))


def tick(high: bool = False) -> np.ndarray:
    """A short clock tick; `high` is used for the last seconds."""
    length = int(SAMPLE_RATE * 0.06)
    t = np.arange(length, dtype=np.float32) / SAMPLE_RATE
    frequency = 1800 if high else 1100
    return (np.sin(2 * np.pi * frequency * t) * _envelope(length, 9)).astype(np.float32) * 0.5


def ding() -> np.ndarray:
    """A two-note chime played when the answer is revealed."""
    length = int(SAMPLE_RATE * 0.9)
    t = np.arange(length, dtype=np.float32) / SAMPLE_RATE
    first = np.sin(2 * np.pi * 880 * t)
    second = np.sin(2 * np.pi * 1318.5 * t) * (t > 0.12)
    return ((first + second) * _envelope(length, 5) * 0.35).astype(np.float32)


def silence(seconds: float) -> np.ndarray:
    return np.zeros(int(round(seconds * SAMPLE_RATE)), dtype=np.float32)


def mix_in(track: np.ndarray, clip: np.ndarray, start_seconds: float, gain: float = 1.0) -> None:
    start = int(round(start_seconds * SAMPLE_RATE))
    if start >= len(track):
        return
    end = min(len(track), start + len(clip))
    track[start:end] += clip[: end - start] * gain


def write_wav(path: Path, samples: np.ndarray) -> None:
    peak = float(np.max(np.abs(samples))) if len(samples) else 0.0
    if peak > 0.98:
        samples = samples * (0.98 / peak)

    pcm = (np.clip(samples, -1, 1) * 32767).astype(np.int16)
    with wave.open(str(path), "wb") as handle:
        handle.setnchannels(1)
        handle.setsampwidth(2)
        handle.setframerate(SAMPLE_RATE)
        handle.writeframes(pcm.tobytes())
