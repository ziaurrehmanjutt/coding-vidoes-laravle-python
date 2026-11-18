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


def _chirp(seconds: float, start_hz: float, end_hz: float) -> np.ndarray:
    length = int(SAMPLE_RATE * seconds)
    t = np.arange(length, dtype=np.float32) / SAMPLE_RATE
    phase = 2 * np.pi * (start_hz * t + (end_hz - start_hz) * t * t / (2 * seconds))
    return np.sin(phase).astype(np.float32)


def whoosh() -> np.ndarray:
    """A quick rising swoosh for the start of the video."""
    seconds = 0.4
    length = int(SAMPLE_RATE * seconds)
    bell = np.sin(np.linspace(0, np.pi, length, dtype=np.float32)) ** 2
    noise = np.random.default_rng(3).uniform(-1, 1, length).astype(np.float32)
    return ((_chirp(seconds, 250, 1800) * 0.5 + noise * 0.35) * bell * 0.45).astype(np.float32)


def drumroll(seconds: float = 3.0) -> np.ndarray:
    """Hits that get faster and louder, building up to the answer."""
    length = int(SAMPLE_RATE * (seconds + 0.1))
    track = np.zeros(length, dtype=np.float32)
    rng = np.random.default_rng(5)
    hit_length = int(SAMPLE_RATE * 0.05)
    t = np.arange(hit_length, dtype=np.float32) / SAMPLE_RATE
    hit = (rng.uniform(-1, 1, hit_length) * 0.6 + np.sin(2 * np.pi * 170 * t)) * _envelope(hit_length, 7)

    moment = 0.0
    while moment < seconds:
        progress = moment / seconds
        start = int(moment * SAMPLE_RATE)
        end = min(length, start + hit_length)
        track[start:end] += hit[: end - start].astype(np.float32) * (0.25 + 0.6 * progress)
        moment += 0.16 - 0.12 * progress
    return track


def buzzer() -> np.ndarray:
    """A short falling 'time is up' sound."""
    seconds = 0.4
    length = int(SAMPLE_RATE * seconds)
    return (_chirp(seconds, 520, 170) * _envelope(length, 3) * 0.35).astype(np.float32)


def pop() -> np.ndarray:
    """The confetti pop."""
    length = int(SAMPLE_RATE * 0.18)
    noise = np.random.default_rng(9).uniform(-1, 1, length).astype(np.float32)
    return ((noise * 0.6 + _chirp(0.18, 700, 220)) * _envelope(length, 9) * 0.5).astype(np.float32)


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
