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

# The same database Laravel uses, so Python can report render progress.
# DB_CONNECTION is "mysql" or "sqlite" and the names match Laravel's .env.
DB_CONNECTION = os.getenv("DB_CONNECTION", "sqlite")
DB_HOST = os.getenv("DB_HOST", "127.0.0.1")
DB_PORT = int(os.getenv("DB_PORT", "3306"))
DB_DATABASE = os.getenv("DB_DATABASE", "prgram_vid")
DB_USERNAME = os.getenv("DB_USERNAME", "root")
DB_PASSWORD = os.getenv("DB_PASSWORD", "")

# Only used when DB_CONNECTION is "sqlite".
DB_PATH = _resolve_path(os.getenv("DB_PATH"), "../database/database.sqlite")

FPS = int(os.getenv("VIDEO_FPS", "30"))
