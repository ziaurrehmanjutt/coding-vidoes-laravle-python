"""A small Markdown reader for the explanation text shown in the video.

Supported: **bold**, *italic*, ***both***, __bold__, _italic_, `inline code`,
# headings, - or * bullet lists, 1. numbered lists and ``` code blocks.
It only parses; drawing is done in frames.py.
"""

from __future__ import annotations

import re
from dataclasses import dataclass, field

REGULAR, BOLD, ITALIC, BOLD_ITALIC, CODE = "regular", "bold", "italic", "bolditalic", "code"

_INLINE = re.compile(
    r"`([^`]+)`"
    r"|\*\*\*(.+?)\*\*\*"
    r"|\*\*(.+?)\*\*"
    r"|__(.+?)__"
    r"|\*(?=\S)(.+?)(?<=\S)\*"
    r"|(?<!\w)_(?=\S)(.+?)(?<=\S)_(?!\w)"
)
_WORD = re.compile(r"(\s*)(\S+)")
_HEADING = re.compile(r"^\s*#{1,6}\s+(.*)$")
_BULLET = re.compile(r"^\s*[-*+]\s+(.*)$")
_NUMBERED = re.compile(r"^\s*(\d+)[.)]\s+(.*)$")


@dataclass(frozen=True)
class Token:
    text: str
    style: str
    space_before: bool


@dataclass
class Block:
    kind: str  # paragraph, bullet, heading, code, blank
    tokens: list[Token] = field(default_factory=list)
    marker: str = ""


def _combine(base: str, extra: str) -> str:
    bold = base in (BOLD, BOLD_ITALIC) or extra in (BOLD, BOLD_ITALIC)
    italic = base in (ITALIC, BOLD_ITALIC) or extra in (ITALIC, BOLD_ITALIC)
    if bold and italic:
        return BOLD_ITALIC
    return BOLD if bold else ITALIC if italic else REGULAR


def _segments(text: str, base: str) -> list[tuple[str, str]]:
    """Split text into (text, style) pieces, handling nested emphasis."""
    pieces: list[tuple[str, str]] = []
    position = 0
    for match in _INLINE.finditer(text):
        if match.start() > position:
            pieces.append((text[position:match.start()], base))
        code, bold_italic, bold, bold_alt, italic, italic_alt = match.groups()
        if code is not None:
            pieces.append((code, CODE))
        elif bold_italic is not None:
            pieces.extend(_segments(bold_italic, _combine(base, BOLD_ITALIC)))
        elif bold is not None or bold_alt is not None:
            pieces.extend(_segments(bold if bold is not None else bold_alt, _combine(base, BOLD)))
        else:
            pieces.extend(_segments(italic if italic is not None else italic_alt, _combine(base, ITALIC)))
        position = match.end()
    if position < len(text):
        pieces.append((text[position:], base))
    return pieces


def _tokenize(text: str, base: str = REGULAR) -> list[Token]:
    tokens: list[Token] = []
    pending_space = False
    for segment, style in _segments(text, base):
        if style == CODE:
            tokens.append(Token(segment, CODE, pending_space))
            pending_space = False
            continue
        for match in _WORD.finditer(segment):
            tokens.append(Token(match.group(2), style, bool(match.group(1)) or pending_space))
            pending_space = False
        if segment[-1:].isspace():
            pending_space = True
    return tokens


def parse(text: str) -> list[Block]:
    blocks: list[Block] = []
    in_code = False

    for raw in text.replace("\r\n", "\n").split("\n"):
        line = raw.rstrip()

        if line.strip().startswith("```"):
            in_code = not in_code
            continue
        if in_code:
            blocks.append(Block("code", [Token(line.replace("\t", "    "), CODE, False)]))
            continue

        if not line.strip():
            if blocks and blocks[-1].kind != "blank":
                blocks.append(Block("blank"))
            continue

        if heading := _HEADING.match(line):
            blocks.append(Block("heading", _tokenize(heading.group(1), BOLD)))
        elif bullet := _BULLET.match(line):
            blocks.append(Block("bullet", _tokenize(bullet.group(1)), marker="•"))
        elif numbered := _NUMBERED.match(line):
            blocks.append(Block("bullet", _tokenize(numbered.group(2)), marker=f"{numbered.group(1)}."))
        else:
            blocks.append(Block("paragraph", _tokenize(line.strip())))

    while blocks and blocks[-1].kind == "blank":
        blocks.pop()
    return blocks


def plain_text(text: str) -> str:
    """The explanation without Markdown symbols (used to estimate reading time)."""
    return " ".join(token.text for block in parse(text) for token in block.tokens)
