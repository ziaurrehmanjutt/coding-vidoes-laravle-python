"""Predict a snippet's output by actually running it in an isolated child process."""

import subprocess
import sys
import tempfile

MAX_OUTPUT_CHARS = 2000


def predict_output(code: str, timeout_seconds: float = 5.0) -> dict:
    """Run the code with `python -I` (isolated mode) in a throwaway directory."""
    with tempfile.TemporaryDirectory() as working_directory:
        try:
            completed = subprocess.run(
                [sys.executable, "-I", "-c", code],
                capture_output=True,
                text=True,
                encoding="utf-8",
                errors="replace",
                timeout=timeout_seconds,
                cwd=working_directory,
                env={"PYTHONIOENCODING": "utf-8", "SYSTEMROOT": _system_root()},
            )
        except subprocess.TimeoutExpired:
            return {"ok": False, "output": "", "error": f"Timed out after {timeout_seconds:g} seconds"}

    output = completed.stdout.rstrip("\r\n")[:MAX_OUTPUT_CHARS]
    error = completed.stderr.strip().splitlines()
    last_error_line = error[-1] if error else ""

    return {
        "ok": completed.returncode == 0,
        "output": output,
        "error": last_error_line[:500],
    }


def _system_root() -> str:
    import os

    return os.environ.get("SYSTEMROOT", "")
