"""Settings loaded from python_services/.env (shared paths with Laravel)."""

import os
from pathlib import Path

from dotenv import load_dotenv

BASE_DIR = Path(__file__).resolve().parent.parent

load_dotenv(BASE_DIR / ".env")


def _resolve_path(value: str | None, default: str) -> Path:
    path = Path(value or default)
    return path if path.is_absolute() else (BASE_DIR / path).resolve()


API_TOKEN = os.getenv("API_TOKEN", "change-me")

# Laravel's `local` disk root. Rendered videos are written below this folder.
LARAVEL_STORAGE_PATH = _resolve_path(os.getenv("LARAVEL_STORAGE_PATH"), "../storage/app/private")

# The same SQLite database Laravel uses, so Python can report render progress.
DB_PATH = _resolve_path(os.getenv("DB_PATH"), "../database/database.sqlite")

FPS = int(os.getenv("VIDEO_FPS", "30"))
