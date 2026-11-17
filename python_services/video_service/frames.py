"""Pillow drawing for every scene of the 1080x1920 (9:16) quiz video."""

from __future__ import annotations

import math
from dataclasses import dataclass

import numpy as np
from PIL import Image, ImageDraw, ImageFilter, ImageFont

from .languages import Language, get_language, token_pattern
from .themes import CODE_COLORS, Color, Theme

WIDTH, HEIGHT = 1080, 1920
MARGIN = 100
CONTENT_WIDTH = WIDTH - 2 * MARGIN

OPTION_HEIGHT = 120
OPTION_GAP = 22
OPTIONS_TOP = 1050
TIMER_BAR_TOP = 995

FONT_FILES = {
    "bold": ["segoeuib.ttf", "arialbd.ttf", "DejaVuSans-Bold.ttf", "LiberationSans-Bold.ttf"],
    "regular": ["segoeui.ttf", "arial.ttf", "DejaVuSans.ttf", "LiberationSans-Regular.ttf"],
    "mono": ["consola.ttf", "cour.ttf", "DejaVuSansMono.ttf", "LiberationMono-Regular.ttf"],
    "monobold": ["consolab.ttf", "courbd.ttf", "DejaVuSansMono-Bold.ttf", "LiberationMono-Bold.ttf"],
}

_font_cache: dict[tuple[str, int], ImageFont.FreeTypeFont] = {}
_mask_cache: dict[tuple[int, int, int], Image.Image] = {}

def load_font(kind: str, size: int) -> ImageFont.FreeTypeFont:
    key = (kind, size)
    if key not in _font_cache:
        font = None
        for file_name in FONT_FILES[kind]:
            try:
                font = ImageFont.truetype(file_name, size)
                break
            except OSError:
                continue
        _font_cache[key] = font or ImageFont.load_default(size)
    return _font_cache[key]


def blend(foreground: Color, background: Color, amount: float) -> Color:
    return tuple(int(f * amount + b * (1 - amount)) for f, b in zip(foreground, background))  # type: ignore[return-value]


def rounded_box(image: Image.Image, box: tuple[int, int, int, int], radius: int, fill: Color, alpha: float = 1.0) -> None:
    """Anti-aliased rounded rectangle (drawn at 3x and scaled down)."""
    left, top, right, bottom = box
    width, height = right - left, bottom - top
    key = (width, height, radius)
    if key not in _mask_cache:
        scale = 3
        mask = Image.new("L", (width * scale, height * scale), 0)
        ImageDraw.Draw(mask).rounded_rectangle(
            (0, 0, width * scale - 1, height * scale - 1), radius * scale, fill=255
        )
        _mask_cache[key] = mask.resize((width, height), Image.LANCZOS)

    mask = _mask_cache[key]
    if alpha < 1.0:
        mask = mask.point(lambda value: int(value * alpha))
    image.paste(fill, (left, top), mask)


def wrap_text(text: str, font: ImageFont.FreeTypeFont, max_width: int) -> list[list[str]]:
    """Wrap into lines of words; blank lines are kept as empty lists."""
    lines: list[list[str]] = []
    for paragraph in text.split("\n"):
        current: list[str] = []
        for word in paragraph.split():
            candidate = " ".join(current + [word])
            if current and font.getlength(candidate) > max_width:
                lines.append(current)
                current = [word]
            else:
                current.append(word)
        lines.append(current)
    return lines


@dataclass
class CodeLayout:
    font: ImageFont.FreeTypeFont
    line_height: int
    lines: list[list[tuple[str, Color]]]
    card_height: int


def highlight(code: str, language: Language) -> list[list[tuple[str, Color]]]:
    """Split code into lines of (text, colour) pieces."""
    lines: list[list[tuple[str, Color]]] = [[]]
    for match in token_pattern(language.key).finditer(code):
        kind = match.lastgroup
        text = match.group()
        color = CODE_COLORS["default"]

        if kind == "comment":
            color = CODE_COLORS["comment"]
        elif kind == "string":
            color = CODE_COLORS["string"]
        elif kind == "number":
            color = CODE_COLORS["number"]
        elif kind == "name":
            following = code[match.end():].lstrip(" ")[:1]
            word = text.lower() if language.ignore_case else text
            if word in language.keywords:
                color = CODE_COLORS["keyword"]
            elif following == "(" or text in language.builtins:
                color = CODE_COLORS["call"]

        for index, piece in enumerate(text.split("\n")):
            if index > 0:
                lines.append([])
            if piece:
                lines[-1].append((piece.replace("\t", "    "), color))
    return lines


def layout_code(code: str, max_height: int, language: Language) -> CodeLayout:
    lines = highlight(code.strip("\n"), language)
    chosen = None
    for size in range(46, 25, -2):
        font = load_font("mono", size)
        line_height = int(size * 1.45)
        widest = max((sum(font.getlength(text) for text, _ in line) for line in lines), default=0)
        height = 84 + len(lines) * line_height + 36
        chosen = CodeLayout(font, line_height, lines, min(height, max_height))
        if widest <= CONTENT_WIDTH - 80 and height <= max_height:
            break
    return chosen  # type: ignore[return-value]


class FrameBuilder:
    """Draws frames for one question using one theme."""

    def __init__(self, theme: Theme, question: dict, channel_name: str | None, think_seconds: int) -> None:
        self.theme = theme
        self.question = question
        self.channel_name = (channel_name or "").strip()
        self.think_seconds = think_seconds

        self.language = get_language(question.get("language"))
        self.options: list[str] = question["options"]
        self.correct = int(question["correct_option"])

        self.background = self._make_background()
        self.question_font = load_font("bold", 56)
        self.question_lines = wrap_text(question["question_text"], self.question_font, CONTENT_WIDTH)[:3]

        code = (question.get("code") or "").strip()
        self.code_quiz = layout_code(code, 500, self.language) if code else None
        self.code_explain = layout_code(code, 420, self.language) if code else None

        self.option_font = self._fit_option_font()
        self.explanation_words = self._explanation_words()
        self._text_cache: dict = {}

    # ------------------------------------------------------------------ setup

    def _make_background(self) -> Image.Image:
        theme = self.theme
        fade = np.linspace(0, 1, HEIGHT, dtype=np.float32)[:, None, None]
        top = np.array(theme.background_top, dtype=np.float32)
        bottom = np.array(theme.background_bottom, dtype=np.float32)
        gradient = np.broadcast_to(top + (bottom - top) * fade, (HEIGHT, WIDTH, 3)).astype(np.uint8)
        image = Image.fromarray(gradient, "RGB")

        glow = Image.new("RGB", (WIDTH, HEIGHT), (0, 0, 0))
        draw = ImageDraw.Draw(glow)
        draw.ellipse((-300, -200, 600, 700), fill=blend(theme.accent, (0, 0, 0), 0.55))
        draw.ellipse((600, 1250, 1500, 2150), fill=blend(theme.accent, (0, 0, 0), 0.4))
        glow = glow.filter(ImageFilter.GaussianBlur(160))

        return Image.fromarray(np.clip(np.asarray(image, dtype=np.int16) + np.asarray(glow, dtype=np.int16) // 2, 0, 255).astype(np.uint8), "RGB")

    def _fit_option_font(self) -> ImageFont.FreeTypeFont:
        available = CONTENT_WIDTH - 140 - 100
        for size in range(46, 25, -2):
            font = load_font("mono", size)
            if all(font.getlength(option) <= available for option in self.options):
                return font
        return load_font("mono", 26)

    def _explanation_words(self) -> str:
        return (self.question.get("explanation") or "").strip()

    # ---------------------------------------------------------------- helpers

    def _new_frame(self) -> tuple[Image.Image, ImageDraw.ImageDraw]:
        image = self.background.copy()
        return image, ImageDraw.Draw(image)

    def _header(self, image: Image.Image, draw: ImageDraw.ImageDraw) -> None:
        font = load_font("bold", 36)
        label = f"{self.language.label.upper()} QUIZ"
        pill_width = int(font.getlength(label)) + 64
        rounded_box(image, (MARGIN, 96, MARGIN + pill_width, 168), 36, self.theme.accent)
        draw.text((MARGIN + pill_width // 2, 132), label, font=font, fill=(255, 255, 255), anchor="mm")

        series_label = (self.question.get("series_label") or "").strip()
        if series_label:
            series_text = series_label.upper()
            series_left = MARGIN + pill_width + 20
            series_width = int(font.getlength(series_text)) + 64
            rounded_box(image, (series_left, 96, series_left + series_width, 168), 36, (255, 255, 255), alpha=0.16)
            draw.text((series_left + series_width // 2, 132), series_text, font=font, fill=self.theme.text, anchor="mm")

        if self.channel_name:
            draw.text((MARGIN + 6, 192), self.channel_name, font=load_font("regular", 30), fill=self.theme.muted)

    def _question_text(self, draw: ImageDraw.ImageDraw, top: int = 250) -> None:
        for index, words in enumerate(self.question_lines):
            draw.text((MARGIN, top + index * 70), " ".join(words), font=self.question_font, fill=self.theme.text)

    def _ring(self, image: Image.Image, draw: ImageDraw.ImageDraw, fraction: float, label: str, color: Color) -> None:
        radius, thickness, scale = 78, 14, 4
        padding = 10
        size = (radius * 2 + padding * 2) * scale
        layer = Image.new("RGBA", (size, size), (0, 0, 0, 0))
        layer_draw = ImageDraw.Draw(layer)
        box = (padding * scale, padding * scale, (padding + radius * 2) * scale, (padding + radius * 2) * scale)
        layer_draw.ellipse(box, outline=(255, 255, 255, 50), width=thickness * scale)
        if fraction > 0:
            layer_draw.arc(box, -90, -90 + 360 * min(fraction, 1.0), fill=color + (255,), width=thickness * scale)
        layer = layer.resize((size // scale, size // scale), Image.LANCZOS)

        center_x, center_y = WIDTH - MARGIN - radius, 150
        image.paste(layer, (center_x - layer.width // 2, center_y - layer.height // 2), layer)
        draw.text((center_x, center_y - 2), label, font=load_font("bold", 66), fill=(255, 255, 255), anchor="mm")

    def _check_badge(self, image: Image.Image, draw: ImageDraw.ImageDraw) -> None:
        radius = 78
        center_x, center_y = WIDTH - MARGIN - radius, 150
        draw.ellipse((center_x - radius, center_y - radius, center_x + radius, center_y + radius), fill=self.theme.correct)
        self._draw_check(draw, center_x, center_y, 34, 14)

    @staticmethod
    def _draw_check(draw: ImageDraw.ImageDraw, center_x: int, center_y: int, size: int, width: int) -> None:
        points = [
            (center_x - size * 0.6, center_y + size * 0.05),
            (center_x - size * 0.15, center_y + size * 0.5),
            (center_x + size * 0.65, center_y - size * 0.45),
        ]
        draw.line(points, fill=(255, 255, 255), width=width, joint="curve")

    def _code_card(self, image: Image.Image, draw: ImageDraw.ImageDraw, layout: CodeLayout, top: int) -> int:
        """Draw the code card and return its bottom edge."""
        bottom = top + layout.card_height
        rounded_box(image, (MARGIN, top, WIDTH - MARGIN, bottom), 36, self.theme.code_card)
        for index, color in enumerate(((255, 95, 86), (255, 189, 46), (39, 201, 63))):
            cx = MARGIN + 44 + index * 34
            draw.ellipse((cx - 9, top + 30 - 9, cx + 9, top + 30 + 9), fill=color)
        draw.text((WIDTH - MARGIN - 40, top + 30), self.language.filename, font=load_font("regular", 26), fill=self.theme.muted, anchor="rm")

        y = top + 72
        for line in layout.lines:
            x = MARGIN + 40
            for text, color in line:
                draw.text((x, y), text, font=layout.font, fill=color)
                x += layout.font.getlength(text)
            y += layout.line_height
        return bottom

    def _options(self, image: Image.Image, draw: ImageDraw.ImageDraw, top: int, reveal: bool) -> None:
        theme = self.theme
        letter_font = load_font("bold", 42)
        for index, option in enumerate(self.options):
            y = top + index * (OPTION_HEIGHT + OPTION_GAP)
            is_correct = index == self.correct

            if reveal and is_correct:
                fill, text_color, badge_color = theme.correct, (255, 255, 255), (255, 255, 255)
                letter_color = theme.correct
            elif reveal:
                fill = blend(theme.card, self.theme.background_bottom, 0.45)
                text_color = blend(theme.muted, fill, 0.55)
                badge_color, letter_color = blend(theme.accent, fill, 0.4), (255, 255, 255)
            else:
                fill, text_color = theme.card, theme.text
                badge_color, letter_color = theme.accent, (255, 255, 255)

            rounded_box(image, (MARGIN, y, WIDTH - MARGIN, y + OPTION_HEIGHT), 40, fill)

            center_y = y + OPTION_HEIGHT // 2
            draw.ellipse((MARGIN + 28, center_y - 38, MARGIN + 104, center_y + 38), fill=badge_color)
            draw.text((MARGIN + 66, center_y - 2), chr(65 + index), font=letter_font, fill=letter_color, anchor="mm")
            draw.text((MARGIN + 140, center_y - 2), option, font=self.option_font, fill=text_color, anchor="lm")

            if reveal and is_correct:
                self._draw_check(draw, WIDTH - MARGIN - 66, center_y, 26, 11)

    def _timer_bar(self, image: Image.Image, remaining: float) -> None:
        fraction = max(0.0, min(1.0, remaining / self.think_seconds))
        rounded_box(image, (MARGIN, TIMER_BAR_TOP, WIDTH - MARGIN, TIMER_BAR_TOP + 18), 9, (255, 255, 255), alpha=0.2)
        if fraction > 0:
            color = self.theme.accent if remaining > 5 else self.theme.warning if remaining > 2.5 else self.theme.danger
            width = max(18, int(CONTENT_WIDTH * fraction))
            rounded_box(image, (MARGIN, TIMER_BAR_TOP, MARGIN + width, TIMER_BAR_TOP + 18), 9, color)

    # ----------------------------------------------------------------- scenes

    def question_scene(self) -> Image.Image:
        image, draw = self._new_frame()
        self._header(image, draw)
        self._ring(image, draw, 1.0, str(self.think_seconds), self.theme.accent)
        self._question_text(draw)
        if self.code_quiz:
            self._code_card(image, draw, self.code_quiz, 470)
        self._timer_bar(image, self.think_seconds)
        self._options(image, draw, OPTIONS_TOP, reveal=False)
        return image

    def think_scene(self, remaining: float) -> Image.Image:
        image, draw = self._new_frame()
        self._header(image, draw)
        color = self.theme.accent if remaining > 5 else self.theme.warning if remaining > 2.5 else self.theme.danger
        self._ring(image, draw, remaining / self.think_seconds, str(max(1, math.ceil(remaining))), color)
        self._question_text(draw)
        if self.code_quiz:
            self._code_card(image, draw, self.code_quiz, 470)
        self._timer_bar(image, remaining)
        self._options(image, draw, OPTIONS_TOP, reveal=False)
        return image

    def reveal_scene(self) -> Image.Image:
        image, draw = self._new_frame()
        self._header(image, draw)
        self._check_badge(image, draw)
        self._question_text(draw)
        if self.code_quiz:
            self._code_card(image, draw, self.code_quiz, 470)
        self._options(image, draw, OPTIONS_TOP, reveal=True)
        return image

    def explain_scene(self, progress: float) -> Image.Image:
        image, draw = self._new_frame()
        theme = self.theme
        self._header(image, draw)

        y = 270
        if self.code_explain:
            y = self._code_card(image, draw, self.code_explain, y) + 36

        letter = chr(65 + self.correct)
        answer_font = load_font("monobold", 44)
        answer_text = f"{letter}  {self.options[self.correct]}"
        pill_width = min(CONTENT_WIDTH, int(answer_font.getlength(answer_text)) + 130)
        rounded_box(image, (MARGIN, y, MARGIN + pill_width, y + 96), 48, theme.correct)
        self._draw_check(draw, MARGIN + 52, y + 48, 22, 10)
        draw.text((MARGIN + 96, y + 46), answer_text, font=answer_font, fill=(255, 255, 255), anchor="lm")
        y += 96 + 34

        text_width = CONTENT_WIDTH - 88
        font, lines, line_height = self._fit_text(self.explanation_words, text_width, HEIGHT - 300 - y - 140)
        card_bottom = y + 100 + len(lines) * line_height + 40
        rounded_box(image, (MARGIN, y, WIDTH - MARGIN, card_bottom), 36, theme.card)
        draw.text((MARGIN + 44, y + 40), "WHY?", font=load_font("bold", 34), fill=theme.accent)

        self._draw_revealed(draw, font, lines, line_height, (MARGIN + 44, y + 100), progress)
        return image

    def outro_scene(self) -> Image.Image:
        image, draw = self._new_frame()
        theme = self.theme
        self._header(image, draw)

        draw.text((WIDTH // 2, 760), "Did you get it right?", font=load_font("bold", 68), fill=theme.text, anchor="mm")
        draw.text((WIDTH // 2, 880), "Follow for more", font=load_font("regular", 50), fill=theme.muted, anchor="mm")
        draw.text((WIDTH // 2, 960), f"{self.language.label} quizzes", font=load_font("bold", 64), fill=theme.accent, anchor="mm")
        next_label = (self.question.get("next_series_label") or "").strip()
        if next_label:
            draw.text((WIDTH // 2, 1090), f"Next up: {next_label}", font=load_font("bold", 50), fill=theme.text, anchor="mm")
        if self.channel_name:
            draw.text((WIDTH // 2, 1200 if next_label else 1090), self.channel_name, font=load_font("bold", 44), fill=theme.muted if next_label else theme.text, anchor="mm")
        return image

    def _fit_text(self, text: str, width: int, max_height: int) -> tuple[ImageFont.FreeTypeFont, list[list[str]], int]:
        """Pick the biggest font size whose wrapped text fits in max_height."""
        key = (text, width, max_height)
        if key not in self._text_cache:
            for size in range(50, 29, -2):
                font = load_font("regular", size)
                lines = wrap_text(text, font, width)
                line_height = int(size * 1.45)
                if len(lines) * line_height <= max_height:
                    break
            self._text_cache[key] = (font, lines, line_height)
        return self._text_cache[key]

    def _draw_revealed(
        self,
        draw: ImageDraw.ImageDraw,
        font: ImageFont.FreeTypeFont,
        lines: list[list[str]],
        line_height: int,
        origin: tuple[int, int],
        progress: float,
    ) -> None:
        """Show wrapped text word by word as `progress` grows from 0 to 1."""
        total_characters = sum(len(word) + 1 for line in lines for word in line) or 1
        visible_characters = progress * total_characters

        seen = 0
        for index, words in enumerate(lines):
            x = origin[0]
            y = origin[1] + index * line_height
            for word in words:
                if seen < visible_characters:
                    draw.text((x, y), word, font=font, fill=self.theme.text)
                x += font.getlength(word + " ")
                seen += len(word) + 1
