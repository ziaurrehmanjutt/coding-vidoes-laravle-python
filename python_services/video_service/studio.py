"""Studio engine: draws a project (objects on a timeline) frame by frame.

The browser preview in resources/js/studio/engine.js follows the same rules, so keep both in step:
  * an object is visible from `start` for `duration` seconds
  * x, y, scale, rotation and opacity can be animated with keyframe tracks (times are relative to `start`)
  * text and code can be revealed letter by letter at `cps` characters per second
"""

from __future__ import annotations

import math
import subprocess
import tempfile
from collections.abc import Callable
from pathlib import Path

import imageio_ffmpeg
import numpy as np
from PIL import Image, ImageDraw

from . import audio, config
from .frames import blend, load_font, rounded_mask
from .languages import get_language
from .frames import highlight
from .themes import get_theme
from .tts import VoiceSettings, speech_samples

WIDTH = 1080
HEIGHT = 1920
SUPERSAMPLE = 2
ENTER_SECONDS = 0.4
EXIT_SECONDS = 0.3
MAX_SECONDS = 120.0
MAX_OBJECTS = 80

ProgressCallback = Callable[[int, str], None]


# ---------------------------------------------------------------- values

def number(value, default: float = 0.0, low: float = -1e6, high: float = 1e6) -> float:
    try:
        result = float(value)
    except (TypeError, ValueError):
        return default
    if math.isnan(result):
        return default
    return min(high, max(low, result))


def color(value, default=(255, 255, 255)) -> tuple[int, int, int]:
    text = str(value or "").lstrip("#")
    if len(text) == 3:
        text = "".join(char * 2 for char in text)
    try:
        return (int(text[0:2], 16), int(text[2:4], 16), int(text[4:6], 16))
    except ValueError:
        return default


def ease(name: str | None, x: float) -> float:
    x = min(1.0, max(0.0, x))
    if name == "ease-in":
        return x * x * x
    if name == "ease-out":
        return 1 - (1 - x) ** 3
    if name == "ease-in-out":
        return 4 * x * x * x if x < 0.5 else 1 - (-2 * x + 2) ** 3 / 2
    if name == "back":
        c1, c3 = 1.70158, 2.70158
        return 1 + c3 * (x - 1) ** 3 + c1 * (x - 1) ** 2
    if name == "bounce":
        n1, d1 = 7.5625, 2.75
        if x < 1 / d1:
            return n1 * x * x
        if x < 2 / d1:
            x -= 1.5 / d1
            return n1 * x * x + 0.75
        if x < 2.5 / d1:
            x -= 2.25 / d1
            return n1 * x * x + 0.9375
        x -= 2.625 / d1
        return n1 * x * x + 0.984375
    return x


def track_value(keys: list, local: float, fallback: float) -> float:
    """Value of one keyframe track. Before the first key it is the first key's value."""
    points = sorted(
        ((number(key.get("t"), 0, 0, MAX_SECONDS), number(key.get("v"), fallback), key.get("ease")) for key in keys if isinstance(key, dict)),
        key=lambda point: point[0],
    )
    if not points:
        return fallback
    if local <= points[0][0]:
        return points[0][1]
    for (t0, v0, easing), (t1, v1, _) in zip(points, points[1:]):
        if local < t1:
            return v0 + (v1 - v0) * ease(easing, (local - t0) / max(1e-6, t1 - t0))
    return points[-1][1]


def state_at(obj: dict, time: float) -> dict | None:
    """Where the object is and how it looks at `time`, or None while it is not on screen."""
    start = number(obj.get("start"), 0, 0, MAX_SECONDS)
    duration = number(obj.get("duration"), 1, 0.1, MAX_SECONDS)
    local = time - start
    if local < 0 or local >= duration:
        return None

    tracks = obj.get("tracks") or {}
    state = {"local": local}
    for prop, fallback in (("x", 0), ("y", 0), ("x2", 0), ("y2", 0), ("scale", 1), ("rotation", 0), ("opacity", 1)):
        base = number(obj.get(prop), fallback)
        keys = tracks.get(prop) if isinstance(tracks, dict) else None
        state[prop] = track_value(keys, local, base) if keys else base

    progress = ease("ease-out", local / ENTER_SECONDS)
    enter = obj.get("enter")
    if enter == "fade":
        state["opacity"] *= progress
    elif enter == "pop":
        state["opacity"] *= min(1.0, progress * 2)
        state["scale"] *= ease("back", local / ENTER_SECONDS)
    elif enter == "slide":
        state["opacity"] *= progress
        state["y"] += (1 - progress) * 80
        state["y2"] += (1 - progress) * 80

    if obj.get("exit") == "fade":
        state["opacity"] *= min(1.0, max(0.0, (duration - local) / EXIT_SECONDS))
    return state


# ---------------------------------------------------------------- drawing

def wrap(text: str, font, max_width: float) -> list[str]:
    lines: list[str] = []
    for paragraph in text.split("\n"):
        line = ""
        for word in paragraph.split(" "):
            trial = f"{line} {word}" if line else word
            if line and font.getlength(trial) > max_width:
                lines.append(line)
                line = word
            else:
                line = trial
        lines.append(line)
    return lines


def typed_lines(lines: list[str], count: int | None) -> list[str]:
    """The lines with only the first `count` characters showing (None shows everything)."""
    if count is None:
        return lines
    shown, remaining = [], count
    for line in lines:
        if remaining < 0:
            break
        shown.append(line[: max(0, remaining)])
        remaining -= len(line) + 1
    return shown


def font_kind(obj: dict) -> str:
    mono = obj.get("font") == "mono"
    bold = bool(obj.get("bold", True))
    italic = bool(obj.get("italic", False))
    if mono:
        return "monobold" if bold else "mono"
    if italic:
        return "bolditalic" if bold else "italic"
    return "bold" if bold else "regular"


def draw_text(obj: dict, size: tuple[int, int], local: float) -> Image.Image:
    width, height = size
    layer = Image.new("RGBA", size, (0, 0, 0, 0))
    draw = ImageDraw.Draw(layer)

    fill = obj.get("fill")
    if fill:
        radius = int(number(obj.get("radius"), 24, 0, 500))
        draw.rounded_rectangle([0, 0, width - 1, height - 1], radius, fill=color(fill))

    padding = int(number(obj.get("padding"), 20 if fill else 0, 0, 400))
    font_size = int(number(obj.get("size"), 64, 8, 400))
    font = load_font(font_kind(obj), font_size)
    lines = wrap(str(obj.get("text") or "")[:2000], font, width - 2 * padding)

    count = None
    if obj.get("reveal") == "type":
        count = int(local * number(obj.get("cps"), 14, 1, 200))
    shown = typed_lines(lines, count)

    line_height = font_size * 1.3
    total = line_height * len(lines)
    top = padding if obj.get("valign") != "middle" else max(padding, (height - total) / 2)
    align = obj.get("align", "left")
    text_color = color(obj.get("color"), (255, 255, 255))

    for index, line in enumerate(shown):
        baseline = top + index * line_height + font_size
        if align == "center":
            anchor_x, anchor = width / 2, "ms"
        elif align == "right":
            anchor_x, anchor = width - padding, "rs"
        else:
            anchor_x, anchor = padding, "ls"
        draw.text((anchor_x, baseline), line, font=font, fill=text_color, anchor=anchor)
    return layer


def draw_code(obj: dict, size: tuple[int, int], local: float) -> Image.Image:
    width, height = size
    layer = Image.new("RGBA", size, (0, 0, 0, 0))
    draw = ImageDraw.Draw(layer)
    theme = get_theme(str(obj.get("theme", "vscode")))
    language = get_language(str(obj.get("language", "python")))

    radius = 28
    draw.rounded_rectangle([0, 0, width - 1, height - 1], radius, fill=theme.code_card)
    bar = 64
    draw.rounded_rectangle([0, 0, width - 1, bar + radius], radius, fill=blend((255, 255, 255), theme.code_card, 0.06))
    draw.rectangle([0, bar, width - 1, bar + radius + 2], fill=theme.code_card)
    for index, dot in enumerate(((255, 95, 86), (255, 189, 46), (39, 201, 63))):
        cx = 36 + index * 34
        draw.ellipse([cx - 10, bar / 2 - 10, cx + 10, bar / 2 + 10], fill=dot)
    draw.text((width / 2, bar / 2), language.filename, font=load_font("regular", 26), fill=theme.muted, anchor="mm")

    code = str(obj.get("code") or "")[:4000].replace("\t", "    ")
    font_size = int(number(obj.get("size"), 36, 12, 120))
    font = load_font("mono", font_size)
    line_height = int(font_size * 1.5)
    gutter = int(font.getlength("00")) + 56
    lines = highlight(code.strip("\n"), language, theme.code)

    remaining = int(local * number(obj.get("cps"), 20, 1, 400)) if obj.get("typing", True) else 10**9
    top = bar + 28
    cursor = None

    for index, pieces in enumerate(lines):
        baseline = top + index * line_height + font_size
        if remaining < 0:
            break
        draw.text((gutter - 24, baseline), str(index + 1), font=font, fill=blend(theme.muted, theme.code_card, 0.5), anchor="rs")
        x = gutter
        for text, piece_color in pieces:
            take = text[: max(0, remaining)]
            if take:
                draw.text((x, baseline), take, font=font, fill=piece_color, anchor="ls")
                x += font.getlength(take)
            remaining -= len(text)
            if remaining < 0:
                break
        cursor = (x, baseline)
        remaining -= 1

    if obj.get("cursor", True) and cursor and int(local * 2) % 2 == 0:
        x, baseline = cursor
        draw.rectangle([x + 2, baseline - font_size * 0.95, x + 5, baseline + font_size * 0.2], fill=theme.accent)
    return layer


def draw_image(image: Image.Image, obj: dict, size: tuple[int, int]) -> Image.Image:
    """The uploaded picture stretched to the box. Transparent PNGs keep their see-through parts."""
    layer = image.resize(size, Image.LANCZOS)
    radius = int(number(obj.get("radius"), 0, 0, 2000))
    if radius:
        mask = rounded_mask(size[0], size[1], min(radius, min(size) // 2))
        alpha = Image.composite(layer.getchannel("A"), Image.new("L", size, 0), mask)
        layer.putalpha(alpha)
    return layer


def draw_shape(obj: dict, size: tuple[int, int], local: float) -> Image.Image:
    width, height = size
    big = Image.new("RGBA", (width * SUPERSAMPLE, height * SUPERSAMPLE), (0, 0, 0, 0))
    draw = ImageDraw.Draw(big)
    stroke_width = int(number(obj.get("strokeWidth"), 0, 0, 80))
    fill = color(obj.get("fill")) if obj.get("fill") else None
    stroke = color(obj.get("stroke")) if stroke_width else None
    inset = stroke_width / 2
    box = [inset * SUPERSAMPLE, inset * SUPERSAMPLE, (width - inset) * SUPERSAMPLE - 1, (height - inset) * SUPERSAMPLE - 1]

    if obj["type"] == "ellipse":
        draw.ellipse(box, fill=fill, outline=stroke, width=stroke_width * SUPERSAMPLE)
    else:
        radius = number(obj.get("radius"), 0, 0, 1000) * SUPERSAMPLE
        draw.rounded_rectangle(box, radius, fill=fill, outline=stroke, width=stroke_width * SUPERSAMPLE)
    return big.resize(size, Image.LANCZOS)


def arrow_geometry(state: dict, obj: dict) -> tuple[tuple[float, float, float, float], int, float]:
    """Bounding box (left, top, right, bottom), stroke width and head size for an arrow."""
    stroke_width = int(number(obj.get("strokeWidth"), 10, 1, 80))
    head = max(stroke_width * 3.2, 28) if obj.get("head", True) else 0
    pad = math.ceil(max(head, stroke_width) + 4)
    box = (
        min(state["x"], state["x2"]) - pad, min(state["y"], state["y2"]) - pad,
        max(state["x"], state["x2"]) + pad, max(state["y"], state["y2"]) + pad,
    )
    return box, stroke_width, head


def draw_arrow(obj: dict, state: dict, box, stroke_width: int, head: float) -> Image.Image:
    left, top, right, bottom = (int(math.floor(box[0])), int(math.floor(box[1])), int(math.ceil(box[2])), int(math.ceil(box[3])))
    width, height = max(1, right - left), max(1, bottom - top)
    big = Image.new("RGBA", (width * SUPERSAMPLE, height * SUPERSAMPLE), (0, 0, 0, 0))
    draw = ImageDraw.Draw(big)
    paint = color(obj.get("stroke"), (255, 255, 255))

    x1, y1 = (state["x"] - left) * SUPERSAMPLE, (state["y"] - top) * SUPERSAMPLE
    x2, y2 = (state["x2"] - left) * SUPERSAMPLE, (state["y2"] - top) * SUPERSAMPLE

    drawing = number(obj.get("draw"), 0.6, 0, 20)
    progress = ease("ease-out", state["local"] / drawing) if drawing else 1.0
    x2, y2 = x1 + (x2 - x1) * progress, y1 + (y2 - y1) * progress

    length = math.hypot(x2 - x1, y2 - y1)
    if length < 1:
        return big.resize((width, height), Image.LANCZOS)
    ux, uy = (x2 - x1) / length, (y2 - y1) / length
    head_px = head * SUPERSAMPLE
    line_end = (x2 - ux * head_px * 0.8, y2 - uy * head_px * 0.8) if head else (x2, y2)

    draw.line([(x1, y1), line_end], fill=paint, width=stroke_width * SUPERSAMPLE)
    if head:
        draw.ellipse([x1 - stroke_width * SUPERSAMPLE / 2, y1 - stroke_width * SUPERSAMPLE / 2,
                      x1 + stroke_width * SUPERSAMPLE / 2, y1 + stroke_width * SUPERSAMPLE / 2], fill=paint)
        half = head_px * 0.5
        draw.polygon([(x2, y2), (x2 - ux * head_px - uy * half, y2 - uy * head_px + ux * half),
                      (x2 - ux * head_px + uy * half, y2 - uy * head_px - ux * half)], fill=paint)
    return big.resize((width, height), Image.LANCZOS)


# ---------------------------------------------------------------- compositing

def paste(canvas: Image.Image, layer: Image.Image, center: tuple[float, float], scale: float, rotation: float, opacity: float) -> None:
    if opacity <= 0 or scale <= 0:
        return
    if scale != 1 or rotation:
        size = (max(1, round(layer.width * scale)), max(1, round(layer.height * scale)))
        layer = layer.resize(size, Image.BICUBIC)
        if rotation:
            layer = layer.rotate(-rotation, resample=Image.BICUBIC, expand=True)
    if opacity < 1:
        alpha = layer.getchannel("A").point(lambda value: int(value * opacity))
        layer = layer.copy()
        layer.putalpha(alpha)

    left, top = round(center[0] - layer.width / 2), round(center[1] - layer.height / 2)
    crop = (max(0, -left), max(0, -top), min(layer.width, canvas.width - left), min(layer.height, canvas.height - top))
    if crop[2] <= crop[0] or crop[3] <= crop[1]:
        return
    canvas.alpha_composite(layer.crop(crop), (max(0, left), max(0, top)))


class Studio:
    """Turns a project document into frames."""

    def __init__(self, document: dict, assets_dir: Path | None = None) -> None:
        self.assets_dir = assets_dir
        self._images: dict[str, Image.Image | None] = {}
        self.objects = [obj for obj in (document.get("objects") or [])[:MAX_OBJECTS] if isinstance(obj, dict)]
        self.duration = number(document.get("duration"), 10, 1, MAX_SECONDS)
        top = color(document.get("background"), (15, 23, 42))
        bottom = color(document.get("background2"), top) if document.get("background2") else top
        gradient = np.linspace(0, 1, HEIGHT, dtype=np.float32)[:, None, None]
        rows = (np.array(top, dtype=np.float32) * (1 - gradient) + np.array(bottom, dtype=np.float32) * gradient)
        self.background = Image.fromarray(np.repeat(rows, WIDTH, axis=1).astype(np.uint8), "RGB").convert("RGBA")
        self._cache: dict[tuple, Image.Image] = {}

    def frame(self, time: float) -> Image.Image:
        canvas = self.background.copy()
        for obj in self.objects:
            kind = obj.get("type")
            if kind not in ("text", "code", "rect", "ellipse", "arrow", "image"):
                continue
            state = state_at(obj, time)
            if state is None:
                continue

            if kind == "arrow":
                box, stroke_width, head = arrow_geometry(state, obj)
                layer = draw_arrow(obj, state, box, stroke_width, head)
                center = (math.floor(box[0]) + layer.width / 2, math.floor(box[1]) + layer.height / 2)
            else:
                size = (int(number(obj.get("w"), 400, 1, 4000)), int(number(obj.get("h"), 200, 1, 4000)))
                if kind == "image" and self._image(obj) is None:
                    continue
                layer = self._layer(obj, kind, size, state["local"])
                center = (state["x"] + size[0] / 2, state["y"] + size[1] / 2)
            paste(canvas, layer, center, state["scale"], state["rotation"], min(1.0, max(0.0, state["opacity"])))
        return canvas.convert("RGB")

    def _layer(self, obj: dict, kind: str, size: tuple[int, int], local: float) -> Image.Image:
        """Draw an object. Static looks are cached because most frames repeat."""
        if kind == "text":
            typing = obj.get("reveal") == "type"
            step = int(local * number(obj.get("cps"), 14, 1, 200)) if typing else 0
            key = (id(obj), size, step)
            builder = lambda: draw_text(obj, size, local)
        elif kind == "image":
            key = (id(obj), size)
            builder = lambda: draw_image(self._image(obj), obj, size)
        elif kind == "code":
            step = (int(local * number(obj.get("cps"), 20, 1, 400)), int(local * 2) % 2)
            key = (id(obj), size, step)
            builder = lambda: draw_code(obj, size, local)
        else:
            key = (id(obj), size)
            builder = lambda: draw_shape(obj, size, local)

        if key not in self._cache:
            if len(self._cache) > 400:
                self._cache.clear()
            self._cache[key] = builder()
        return self._cache[key]

    def _image(self, obj: dict) -> Image.Image | None:
        """Open an uploaded picture once. Only files inside the project's own assets folder are read."""
        name = Path(str(obj.get("asset") or "")).name
        if not name or self.assets_dir is None:
            return None
        if name not in self._images:
            try:
                path = (self.assets_dir / name).resolve()
                if self.assets_dir.resolve() not in path.parents:
                    raise OSError("outside the assets folder")
                with Image.open(path) as opened:
                    self._images[name] = opened.convert("RGBA")
            except (OSError, ValueError):
                self._images[name] = None
        return self._images[name]

    def voice_clips(self) -> list[dict]:
        return [obj for obj in self.objects if obj.get("type") == "voice" and str(obj.get("text") or "").strip()]


# ---------------------------------------------------------------- video

def render_project(document: dict, settings: dict, output_path: str, report: ProgressCallback, resolve, assets_dir: Path | None = None) -> tuple[float, str | None]:
    """Render the project to an MP4 and return (seconds, warning or None)."""
    destination = resolve(output_path)
    destination.parent.mkdir(parents=True, exist_ok=True)
    partial = destination.with_name(destination.stem + ".partial.mp4")

    studio = Studio(document, assets_dir)
    fps = config.FPS
    total_frames = max(1, round(studio.duration * fps))
    total_seconds = total_frames / fps

    report(3, "Preparing")
    warning = None
    track = np.zeros(int(round(total_seconds * audio.SAMPLE_RATE)), dtype=np.float32)

    clips = studio.voice_clips()
    for position, clip in enumerate(clips):
        report(5 + int(15 * position / max(1, len(clips))), f"Recording voice ({position + 1}/{len(clips)})")
        voice = VoiceSettings(
            str(clip.get("voice") or settings.get("voice") or "en-US-AndrewMultilingualNeural"),
            int(settings.get("speech_rate", 0)), int(settings.get("speech_pitch", 0)), int(settings.get("speech_volume", 0)),
        )
        try:
            audio.mix_in(track, speech_samples(str(clip.get("text") or "")[:1000], voice), number(clip.get("start"), 0, 0, MAX_SECONDS))
        except Exception as exc:  # a voice problem must not stop the video
            warning = f"Video is ready, but the voice was unavailable ({type(exc).__name__})"
            break

    if document.get("music"):
        from .renderer import music_under  # the same lo-fi loop the quiz videos use, quieter while someone speaks

        track = np.clip(track + music_under(track, len(track)), -1.0, 1.0)

    quality = {
        "standard": ("23", "veryfast", "128k"), "high": ("17", "medium", "192k"), "max": ("12", "slow", "256k"),
    }.get(settings.get("quality", "high"), ("17", "medium", "192k"))

    with tempfile.TemporaryDirectory() as folder:
        wav_path, log_path = Path(folder) / "audio.wav", Path(folder) / "ffmpeg.log"
        audio.write_wav(wav_path, track)
        command = [
            imageio_ffmpeg.get_ffmpeg_exe(), "-y", "-loglevel", "error",
            "-f", "rawvideo", "-pix_fmt", "rgb24", "-s", f"{WIDTH}x{HEIGHT}", "-r", str(fps), "-i", "-",
            "-i", str(wav_path),
            "-c:v", "libx264", "-preset", quality[1], "-crf", quality[0], "-pix_fmt", "yuv420p",
            "-c:a", "aac", "-b:a", quality[2], "-shortest", "-movflags", "+faststart", str(partial),
        ]
        with open(log_path, "wb") as log_file:
            process = subprocess.Popen(command, stdin=subprocess.PIPE, stderr=log_file)
            try:
                for index in range(total_frames):
                    process.stdin.write(studio.frame(index / fps).tobytes())
                    if index % 15 == 0:
                        report(20 + int(78 * index / total_frames), "Rendering frames")
            except BrokenPipeError:
                pass
            finally:
                try:
                    process.stdin.close()
                except BrokenPipeError:
                    pass
                process.wait()

        if process.returncode != 0:
            partial.unlink(missing_ok=True)
            raise RuntimeError(f"ffmpeg failed: {log_path.read_text(errors='replace')[-300:]}")

    partial.replace(destination)
    return total_seconds, warning
