"""Colour palettes for the video themes chosen on the Laravel settings page."""

from dataclasses import dataclass

Color = tuple[int, int, int]


@dataclass(frozen=True)
class Theme:
    background_top: Color
    background_bottom: Color
    accent: Color
    card: Color
    code_card: Color
    text: Color
    muted: Color
    correct: Color
    warning: Color
    danger: Color


THEMES: dict[str, Theme] = {
    "midnight": Theme(
        background_top=(12, 16, 40), background_bottom=(34, 44, 98), accent=(99, 102, 241),
        card=(33, 40, 82), code_card=(13, 17, 36), text=(255, 255, 255), muted=(166, 176, 214),
        correct=(34, 197, 94), warning=(250, 204, 21), danger=(239, 68, 68),
    ),
    "ocean": Theme(
        background_top=(4, 28, 44), background_bottom=(10, 96, 112), accent=(45, 212, 191),
        card=(12, 62, 80), code_card=(4, 22, 34), text=(255, 255, 255), muted=(160, 210, 214),
        correct=(34, 197, 94), warning=(250, 204, 21), danger=(248, 113, 113),
    ),
    "sunset": Theme(
        background_top=(40, 12, 66), background_bottom=(168, 58, 70), accent=(251, 146, 60),
        card=(84, 30, 82), code_card=(30, 10, 46), text=(255, 255, 255), muted=(226, 190, 210),
        correct=(74, 222, 128), warning=(250, 204, 21), danger=(239, 68, 68),
    ),
    "forest": Theme(
        background_top=(5, 30, 22), background_bottom=(22, 86, 48), accent=(74, 222, 128),
        card=(18, 64, 44), code_card=(4, 22, 14), text=(255, 255, 255), muted=(176, 214, 190),
        correct=(250, 204, 21), warning=(250, 204, 21), danger=(239, 68, 68),
    ),
}

# Syntax colours for the code card (One Dark inspired).
CODE_COLORS = {
    "keyword": (198, 120, 221),
    "string": (152, 195, 121),
    "number": (209, 154, 102),
    "comment": (112, 124, 146),
    "call": (97, 175, 239),
    "default": (232, 234, 240),
}


def get_theme(name: str) -> Theme:
    return THEMES.get(name, THEMES["midnight"])
