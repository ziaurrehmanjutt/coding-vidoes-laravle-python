"""Predict a snippet's output by actually running it in an isolated child process."""

import os
import shutil
import subprocess
import sys
import tempfile

MAX_OUTPUT_CHARS = 2000

# How to run a snippet for each language that can be executed on this machine.
RUNNERS = {
    "python": ("python", [sys.executable, "-I", "-c"]),
    "javascript": ("node", ["node", "-e"]),
    "php": ("php", ["php", "-r"]),
}


def _command(language: str, code: str) -> list[str] | None:
    runner = RUNNERS.get(language)
    if runner is None:
        return None

    _, prefix = runner
    executable = prefix[0] if language == "python" else shutil.which(prefix[0])
    if executable is None:
        return None

    return [executable, *prefix[1:], code]


def predict_output(code: str, timeout_seconds: float = 5.0, language: str = "python") -> dict:
    """Run the code in a throwaway directory and return what it printed."""
    command = _command(language, code)
    if command is None:
        names = ", ".join(sorted(RUNNERS))
        return {
            "ok": False,
            "supported": False,
            "output": "",
            "error": f"Running {language} is not available here. Supported: {names}.",
        }

    with tempfile.TemporaryDirectory() as working_directory:
        try:
            completed = subprocess.run(
                command,
                capture_output=True,
                text=True,
                encoding="utf-8",
                errors="replace",
                timeout=timeout_seconds,
                cwd=working_directory,
                env={
                    "PYTHONIOENCODING": "utf-8",
                    "SYSTEMROOT": os.environ.get("SYSTEMROOT", ""),
                    "PATH": os.environ.get("PATH", ""),
                },
            )
        except subprocess.TimeoutExpired:
            return {
                "ok": False,
                "supported": True,
                "output": "",
                "error": f"Timed out after {timeout_seconds:g} seconds",
            }

    output = completed.stdout.rstrip("\r\n")[:MAX_OUTPUT_CHARS]
    error_lines = [line for line in completed.stderr.strip().splitlines() if line.strip()]

    return {
        "ok": completed.returncode == 0,
        "supported": True,
        "output": output,
        "error": (error_lines[-1] if error_lines else "")[:500],
    }
