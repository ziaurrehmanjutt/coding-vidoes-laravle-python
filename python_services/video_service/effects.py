"""Playful drawings: the robot mascot and the confetti burst."""

from __future__ import annotations

import math
import random

from PIL import Image, ImageDraw

from .themes import Color, Theme

SCALE = 4
BASE_WIDTH, BASE_HEIGHT = 220, 260
WHITE = (255, 255, 255)
SWEAT = (125, 200, 255)
CHEEK = (233, 120, 150)

CONFETTI_COLORS: list[Color] = [
    (250, 204, 21), (244, 114, 182), (96, 165, 250), (74, 222, 128), (251, 146, 60), (255, 255, 255),
]

_mascot_cache: dict[tuple, Image.Image] = {}


def ease_out_back(value: float) -> float:
    """0 to 1 with a small overshoot, so things 'pop' into place."""
    value = max(0.0, min(1.0, value))
    c1 = 1.70158
    c3 = c1 + 1
    return 1 + c3 * (value - 1) ** 3 + c1 * (value - 1) ** 2


def mascot_image(expression: str, theme: Theme, height: int) -> Image.Image:
    """The robot buddy. Expressions: smile, think, hurry, happy."""
    key = (expression, theme.accent, theme.code_card, height)
    if key in _mascot_cache:
        return _mascot_cache[key]

    def sc(*values: float) -> list[int]:
        return [int(value * SCALE) for value in values]

    canvas = Image.new("RGBA", (BASE_WIDTH * SCALE, BASE_HEIGHT * SCALE), (0, 0, 0, 0))
    draw = ImageDraw.Draw(canvas)
    screen = theme.code_card
    line = 7 * SCALE

    draw.line(sc(110, 74, 110, 40), fill=theme.muted, width=8 * SCALE)
    draw.ellipse(sc(97, 18, 123, 44), fill=theme.warning)
    draw.ellipse(sc(30, 70, 190, 230), fill=theme.accent)
    draw.ellipse(sc(46, 82, 100, 118), fill=tuple(min(255, int(c + (255 - c) * 0.3)) for c in theme.accent))
    draw.rounded_rectangle(sc(46, 110, 174, 198), radius=36 * SCALE, fill=screen)

    def eye(center_x: float, radius: float, pupil: float, shift: tuple[float, float] = (0, 0)) -> None:
        center_y = 144
        draw.ellipse(sc(center_x - radius, center_y - radius, center_x + radius, center_y + radius), fill=WHITE)
        px, py = center_x + shift[0], center_y + shift[1]
        draw.ellipse(sc(px - pupil, py - pupil, px + pupil, py + pupil), fill=screen)

    if expression == "think":
        eye(82, 15, 7, (5, -5))
        eye(138, 15, 7, (5, -5))
        draw.line(sc(66, 120, 98, 113), fill=WHITE, width=6 * SCALE)
        draw.line(sc(94, 178, 128, 171), fill=WHITE, width=line)
        from .frames import load_font

        draw.text(sc(180, 62), "?", font=load_font("bold", 80 * SCALE), fill=theme.warning, anchor="mm")
    elif expression == "hurry":
        eye(82, 18, 5)
        eye(138, 18, 5)
        draw.ellipse(sc(98, 168, 122, 194), fill=WHITE)
        draw.ellipse(sc(104, 174, 116, 188), fill=screen)
        draw.polygon(sc(186, 90, 175, 112, 197, 112), fill=SWEAT)
        draw.ellipse(sc(175, 104, 197, 126), fill=SWEAT)
    elif expression == "happy":
        for left in (64, 120):
            draw.arc(sc(left, 130, left + 36, 162), 200, 340, fill=WHITE, width=line)
        draw.pieslice(sc(80, 150, 140, 198), 0, 180, fill=WHITE)
        draw.ellipse(sc(96, 178, 124, 198), fill=(255, 120, 150))
        draw.ellipse(sc(52, 164, 72, 178), fill=CHEEK)
        draw.ellipse(sc(148, 164, 168, 178), fill=CHEEK)
    else:
        eye(82, 14, 7)
        eye(138, 14, 7)
        draw.arc(sc(84, 150, 136, 188), 15, 165, fill=WHITE, width=line)
        draw.ellipse(sc(54, 166, 72, 180), fill=CHEEK)
        draw.ellipse(sc(148, 166, 166, 180), fill=CHEEK)

    width = int(BASE_WIDTH * height / BASE_HEIGHT)
    image = canvas.resize((width, height), Image.LANCZOS)
    _mascot_cache[key] = image
    return image


def draw_confetti(
    draw: ImageDraw.ImageDraw,
    age: float,
    origin: tuple[int, int],
    count: int = 90,
    life: float = 1.9,
    seed: int = 7,
) -> None:
    """Paper pieces thrown up from `origin`. `age` is seconds since the burst; equal ages give equal frames."""
    if age <= 0 or age > life:
        return

    rng = random.Random(seed)
    for _ in range(count):
        angle = rng.uniform(-math.pi * 0.92, -math.pi * 0.08)
        speed = rng.uniform(420, 1000)
        spin = rng.uniform(-9, 9)
        width = rng.randint(10, 20)
        height = rng.randint(18, 30)
        color = rng.choice(CONFETTI_COLORS)
        delay = rng.uniform(0, 0.12)
        sway = rng.uniform(0, math.tau)

        moment = age - delay
        if moment <= 0:
            continue

        x = origin[0] + math.cos(angle) * speed * moment * 0.7 + math.sin(moment * 5 + sway) * 14
        y = origin[1] + math.sin(angle) * speed * moment + 0.5 * 1400 * moment * moment
        fade = 1.0 - max(0.0, (moment - life * 0.6) / (life * 0.4))
        if fade <= 0:
            continue

        rotation = spin * moment
        half_w, half_h = width * fade / 2, height * fade / 2 * abs(math.cos(moment * 6 + sway)) + 2
        corners = []
        for dx, dy in ((-half_w, -half_h), (half_w, -half_h), (half_w, half_h), (-half_w, half_h)):
            corners.append((
                x + dx * math.cos(rotation) - dy * math.sin(rotation),
                y + dx * math.sin(rotation) + dy * math.cos(rotation),
            ))
        draw.polygon(corners, fill=color)
