"""Short attention-grabbing lines shown and spoken at the very start of a video."""

from __future__ import annotations

import zlib

from .languages import get_language

HOOKS = [
    "99% get this wrong!",
    "Can you solve this?",
    "Think you know {language}?",
    "Most people fail this one!",
    "Don't scroll - try this!",
    "Quick! Pick the answer!",
]


def pick_hook(question: dict) -> str:
    """The question's own hook if it has one, otherwise a stable pick from the list."""
    own = (question.get("hook") or "").strip()
    if own:
        return own

    key = f"{question.get('title', '')}{question['question_text']}".encode()
    return HOOKS[zlib.crc32(key) % len(HOOKS)].format(language=get_language(question.get("language")).label)
