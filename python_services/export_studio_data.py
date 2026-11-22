"""Writes the language and theme data the browser editor needs, so the live preview matches the render.

Run from python_services/:  .venv/Scripts/python export_studio_data.py
"""

import json
from pathlib import Path

from video_service.languages import LANGUAGES
from video_service.themes import THEMES

target = Path(__file__).resolve().parent.parent / "resources" / "js" / "studio" / "syntax.json"

data = {
    "languages": {
        key: {
            "label": language.label,
            "filename": language.filename,
            "keywords": sorted(language.keywords),
            "builtins": sorted(language.builtins),
            "comment": language.comment,
            "string": language.string,
            "ignoreCase": language.ignore_case,
        }
        for key, language in LANGUAGES.items()
    },
    "themes": {
        key: {
            "codeCard": "#%02x%02x%02x" % theme.code_card,
            "muted": "#%02x%02x%02x" % theme.muted,
            "accent": "#%02x%02x%02x" % theme.accent,
            "code": {name: "#%02x%02x%02x" % value for name, value in theme.code.items()},
        }
        for key, theme in THEMES.items()
    },
}

target.write_text(json.dumps(data, separators=(",", ":")), encoding="utf-8")
print(f"Wrote {target} ({target.stat().st_size} bytes)")
