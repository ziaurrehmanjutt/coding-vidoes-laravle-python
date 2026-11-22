"""Progress updates written straight into Laravel's generated_videos table."""

import sqlite3
from datetime import datetime, timezone

from . import config


def update_video(
    video_id: int,
    *,
    status: str,
    progress: int,
    message: str | None = None,
    path: str | None = None,
    duration_seconds: float | None = None,
) -> None:
    now = datetime.now(timezone.utc).strftime("%Y-%m-%d %H:%M:%S")

    connection = sqlite3.connect(config.DB_PATH, timeout=30)
    try:
        connection.execute("PRAGMA busy_timeout = 30000")
        connection.execute(
            """
            UPDATE generated_videos
            SET status = ?, progress = ?, message = ?,
                path = COALESCE(?, path),
                duration_seconds = COALESCE(?, duration_seconds),
                updated_at = ?
            WHERE id = ?
            """,
            (status, progress, message, path, duration_seconds, now, video_id),
        )
        connection.commit()
    finally:
        connection.close()


def update_project(
    project_id: int,
    *,
    status: str,
    progress: int,
    message: str | None = None,
    path: str | None = None,
    duration_seconds: float | None = None,
) -> None:
    """Same as update_video, but for the studio projects table."""
    now = datetime.now(timezone.utc).strftime("%Y-%m-%d %H:%M:%S")

    connection = sqlite3.connect(config.DB_PATH, timeout=30)
    try:
        connection.execute("PRAGMA busy_timeout = 30000")
        connection.execute(
            """
            UPDATE projects
            SET render_status = ?, render_progress = ?, render_message = ?,
                video_path = COALESCE(?, video_path),
                duration_seconds = COALESCE(?, duration_seconds),
                updated_at = ?
            WHERE id = ?
            """,
            (status, progress, message, path, duration_seconds, now, project_id),
        )
        connection.commit()
    finally:
        connection.close()
