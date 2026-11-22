"""Colour palettes for the video themes chosen on the Laravel settings page."""

from dataclasses import dataclass, field

Color = tuple[int, int, int]

# Syntax colours for the code card (One Dark inspired). Themes can bring their own.
CODE_COLORS: dict[str, Color] = {
    "keyword": (198, 120, 221),
    "string": (152, 195, 121),
    "number": (209, 154, 102),
    "comment": (112, 124, 146),
    "call": (97, 175, 239),
    "default": (232, 234, 240),
}


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
    code: dict[str, Color] = field(default_factory=lambda: dict(CODE_COLORS))


THEMES: dict[str, Theme] = {
    "vscode": Theme(
        background_top=(20, 20, 24), background_bottom=(38, 42, 52), accent=(0, 122, 204),
        card=(45, 48, 56), code_card=(30, 30, 30), text=(255, 255, 255), muted=(160, 168, 182),
        correct=(34, 197, 94), warning=(250, 204, 21), danger=(239, 68, 68),
        code={
            "keyword": (197, 134, 192), "string": (206, 145, 120), "number": (181, 206, 168),
            "comment": (106, 153, 85), "call": (220, 220, 170), "default": (212, 212, 212),
        },
    ),
    "dracula": Theme(
        background_top=(33, 34, 44), background_bottom=(68, 71, 90), accent=(189, 147, 249),
        card=(68, 71, 90), code_card=(40, 42, 54), text=(248, 248, 242), muted=(165, 172, 205),
        correct=(80, 250, 123), warning=(241, 250, 140), danger=(255, 85, 85),
        code={
            "keyword": (255, 121, 198), "string": (241, 250, 140), "number": (189, 147, 249),
            "comment": (98, 114, 164), "call": (80, 250, 123), "default": (248, 248, 242),
        },
    ),
    "github": Theme(
        background_top=(13, 17, 23), background_bottom=(30, 38, 52), accent=(88, 166, 255),
        card=(33, 38, 45), code_card=(13, 17, 23), text=(240, 246, 252), muted=(139, 148, 158),
        correct=(63, 185, 80), warning=(210, 153, 34), danger=(248, 81, 73),
        code={
            "keyword": (255, 123, 114), "string": (165, 214, 255), "number": (121, 192, 255),
            "comment": (139, 148, 158), "call": (210, 168, 255), "default": (230, 237, 243),
        },
    ),
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


def get_theme(name: str) -> Theme:
    return THEMES.get(name, THEMES["midnight"])
