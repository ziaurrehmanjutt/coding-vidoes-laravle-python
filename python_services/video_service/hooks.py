"""Short attention-grabbing lines shown and spoken at the very start of a video."""

from __future__ import annotations

import zlib

from .languages import get_language

HOOKS = [
    "99% get this wrong!",
    "Can you solve it in {seconds} seconds?",
    "Only real coders get this!",
    "Think you know {language}?",
    "Most people fail this one!",
    "Don't scroll - try this!",
]


def pick_hook(question: dict, think_seconds: int) -> str:
    """Pick a hook by the question, so the same question always gets the same one."""
    key = f"{question.get('title', '')}{question['question_text']}".encode()
    template = HOOKS[zlib.crc32(key) % len(HOOKS)]
    return template.format(seconds=think_seconds, language=get_language(question.get("language")).label)
