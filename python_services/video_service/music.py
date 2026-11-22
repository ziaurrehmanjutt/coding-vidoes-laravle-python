"""A soft lo-fi style background loop, generated with numpy (no music files, no licences)."""

from __future__ import annotations

import numpy as np

from .audio import SAMPLE_RATE

TEMPO = 92
# A minor, F major, C major, G major as MIDI notes.
CHORDS = [(57, 60, 64), (53, 57, 60), (48, 52, 55), (55, 59, 62)]


def _frequency(note: int) -> float:
    return 440.0 * 2 ** ((note - 69) / 12)


def _add(track: np.ndarray, clip: np.ndarray, start_seconds: float) -> None:
    start = int(start_seconds * SAMPLE_RATE)
    if start >= len(track):
        return
    end = min(len(track), start + len(clip))
    track[start:end] += clip[: end - start]


def _tone(note: int, seconds: float, decay: float, harmonics: float = 0.3) -> np.ndarray:
    t = np.arange(int(seconds * SAMPLE_RATE), dtype=np.float32) / SAMPLE_RATE
    frequency = _frequency(note)
    wave = np.sin(2 * np.pi * frequency * t) + harmonics * np.sin(4 * np.pi * frequency * t)
    attack = np.minimum(1.0, t / 0.01)
    return (wave * np.exp(-decay * t) * attack).astype(np.float32)


def _smooth(samples: np.ndarray, width: int) -> np.ndarray:
    kernel = np.ones(width, dtype=np.float32) / width
    return np.convolve(samples, kernel, mode="same").astype(np.float32)


def background(seconds: float) -> np.ndarray:
    """A loop of chords, bass, a plucked pattern and a light beat, `seconds` long, peaking near 1.0."""
    track = np.zeros(int(seconds * SAMPLE_RATE), dtype=np.float32)
    rng = np.random.default_rng(11)
    beat = 60 / TEMPO
    bar = beat * 4

    kick_length = int(0.2 * SAMPLE_RATE)
    kick_t = np.arange(kick_length, dtype=np.float32) / SAMPLE_RATE
    kick = (np.sin(2 * np.pi * 50 * kick_t + 9 * np.exp(-kick_t * 25)) * np.exp(-kick_t * 14)).astype(np.float32)

    def noise_hit(seconds_long: float, decay: float) -> np.ndarray:
        length = int(seconds_long * SAMPLE_RATE)
        raw = rng.uniform(-1, 1, length).astype(np.float32)
        bright = raw - _smooth(raw, 6)
        return bright * np.exp(-decay * np.arange(length, dtype=np.float32) / SAMPLE_RATE)

    snare = noise_hit(0.16, 22)
    hat = noise_hit(0.05, 70)

    bars = int(seconds / bar) + 2
    for index in range(bars):
        chord = CHORDS[index % len(CHORDS)]
        start = index * bar

        for note in chord:
            _add(track, _tone(note, bar + 0.4, 0.9, 0.15) * 0.07, start)

        _add(track, _tone(chord[0] - 12, beat * 1.6, 2.2, 0.2) * 0.30, start)
        _add(track, _tone(chord[0] - 12, beat * 1.4, 2.5, 0.2) * 0.22, start + beat * 2.5)

        for step in range(8):
            note = chord[(step * 2) % 3] + 12 + (12 if step % 4 == 3 else 0)
            _add(track, _tone(note, 0.45, 7.0, 0.5) * 0.09, start + step * beat / 2)

        for step in (0, 2):
            _add(track, kick * 0.55, start + step * beat)
        for step in (1, 3):
            _add(track, snare * 0.16, start + step * beat)
        for step in range(8):
            _add(track, hat * (0.09 if step % 2 else 0.05), start + step * beat / 2)

    track = _smooth(track, 3)
    peak = float(np.max(np.abs(track))) or 1.0
    track = track / peak

    fade = int(0.6 * SAMPLE_RATE)
    track[:fade] *= np.linspace(0, 1, fade, dtype=np.float32)
    track[-fade:] *= np.linspace(1, 0, fade, dtype=np.float32)
    return track
