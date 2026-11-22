"""Progress updates written straight into Laravel's database (MySQL, or the SQLite file as a fallback)."""

import sqlite3
from datetime import datetime, timezone

from . import config


def _connect():
    """Return (connection, placeholder) for the database Laravel uses."""
    if config.DB_CONNECTION == "mysql":
        import pymysql

        connection = pymysql.connect(
            host=config.DB_HOST,
            port=config.DB_PORT,
            user=config.DB_USERNAME,
            password=config.DB_PASSWORD,
            database=config.DB_DATABASE,
            charset="utf8mb4",
            connect_timeout=10,
            autocommit=False,
        )
        return connection, "%s"

    connection = sqlite3.connect(config.DB_PATH, timeout=30)
    connection.execute("PRAGMA busy_timeout = 30000")
    return connection, "?"


def _update(table: str, columns: dict[str, str], row_id: int, *, status: str, progress: int,
            message: str | None, path: str | None, duration_seconds: float | None) -> None:
    """Update one row. `columns` maps the logical names to this table's column names."""
    now = datetime.now(timezone.utc).strftime("%Y-%m-%d %H:%M:%S")
    connection, marker = _connect()

    try:
        cursor = connection.cursor()
        cursor.execute(
            f"""
            UPDATE {table}
            SET {columns['status']} = {marker}, {columns['progress']} = {marker}, {columns['message']} = {marker},
                {columns['path']} = COALESCE({marker}, {columns['path']}),
                duration_seconds = COALESCE({marker}, duration_seconds),
                updated_at = {marker}
            WHERE id = {marker}
            """,
            (status, progress, message, path, duration_seconds, now, row_id),
        )
        connection.commit()
    finally:
        connection.close()


def update_video(
    video_id: int,
    *,
    status: str,
    progress: int,
    message: str | None = None,
    path: str | None = None,
    duration_seconds: float | None = None,
) -> None:
    _update(
        "generated_videos",
        {"status": "status", "progress": "progress", "message": "message", "path": "path"},
        video_id, status=status, progress=progress, message=message, path=path, duration_seconds=duration_seconds,
    )


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
    _update(
        "projects",
        {"status": "render_status", "progress": "render_progress", "message": "render_message", "path": "video_path"},
        project_id, status=status, progress=progress, message=message, path=path, duration_seconds=duration_seconds,
    )
