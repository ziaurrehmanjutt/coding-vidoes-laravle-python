# Coding Quiz Shorts

Make vertical (1080x1920) programming quiz videos for YouTube Shorts, Reels and TikTok.

Each video shows a question with code and options A to D, a thinking countdown, the correct answer, and a spoken explanation. You manage the questions in a Laravel web app. A Python (FastAPI) service renders the videos and runs the code to predict its output.

```
Browser ──> Laravel (UI, database, files)  ──HTTP + token──>  Python service (FastAPI)
                 │                                                  │
                 └────── shared SQLite database + storage ──────────┘
```

## What it does

- **Questions:** code, four options, the correct answer, an explanation, a language and a category.
- **Languages:** Python, JavaScript, TypeScript, PHP, Java, C#, C++, Go and SQL. Python, JavaScript and PHP can also be run to check the answer.
- **Categories, search and filters:** filter by language, category, uploaded or not, and series. Search the question text and code.
- **Series numbering:** give a question "Day 5" or "Challenge 12". The number is saved and shown in the video (badge, narration and "Next up: Day 6").
- **Upload details:** an upload title, description and hashtags per question. They are filled in automatically and you can edit them or regenerate them.
- **Upload queue:** what is left to post, in order, with copy buttons, a download button and "Mark as uploaded".
- **Video settings:** voice (with preview), speech rate, pitch, volume, theme, think time, tick sound, channel name and quality (standard, high, max).
- **Videos page:** live progress while rendering, then watch and download the MP4.

## Requirements

| Tool | Version |
| --- | --- |
| PHP | 8.4 |
| Composer | 2.x |
| Node.js and npm | current LTS |
| Python | 3.11 or newer (3.12 is tested) |
| Internet | needed while rendering, because the voice comes from Microsoft Edge TTS |

ffmpeg does not need to be installed. The Python package `imageio-ffmpeg` brings its own copy.

## Setup

Run these from the project folder. The Windows commands are for PowerShell. On macOS or Linux use the same commands, with the differences noted.

### 1. Laravel

```bash
composer run setup
```

This installs the PHP packages, creates `.env`, generates the app key, runs the migrations (SQLite) and builds the front end.

If `database/database.sqlite` does not exist yet, create an empty one first:

```powershell
New-Item database/database.sqlite -ItemType File
```

(macOS or Linux: `touch database/database.sqlite`)

### 2. Python service

```powershell
cd python_services
python -m venv .venv
.venv\Scripts\python.exe -m pip install -r requirements.txt
Copy-Item .env.example .env
cd ..
```

On macOS or Linux use `.venv/bin/python` and `cp .env.example .env`.

The folder must be named `.venv`. The dev command looks for it by that name.

### 3. Connect the two with a shared token

Put the same secret in both files:

| File | Setting |
| --- | --- |
| `.env` (Laravel) | `VIDEO_PYTHON_TOKEN=your-secret` |
| `python_services/.env` | `API_TOKEN=your-secret` |

The other settings already work with the default folder layout:

| Setting | Default | Meaning |
| --- | --- | --- |
| `VIDEO_PYTHON_URL` (Laravel) | `http://127.0.0.1:8001` | Where Laravel finds the Python service |
| `LARAVEL_STORAGE_PATH` (Python) | `../storage/app/private` | Where videos are written, which is Laravel's private storage |
| `DB_PATH` (Python) | `../database/database.sqlite` | The same database Laravel uses |
| `VIDEO_FPS` (Python) | `30` | Frames per second |

### 4. Add the example questions (optional)

```bash
php artisan db:seed --class=QuestionSeeder
```

This adds 29 checked questions (15 Python, 8 JavaScript, 6 PHP) with categories. Python is numbered Day 1 to 15 and the others Challenge 1 to 14. It is safe to run again, because it never overwrites or duplicates anything.

## Run

```bash
composer run dev
```

This starts everything together: the Laravel server, the Python service on port 8001, the queue worker, logs and Vite.

Open **http://localhost:8000**.

To run the pieces by hand instead:

```powershell
php artisan serve
cd python_services
.venv\Scripts\python.exe -m uvicorn video_service.main:app --host 127.0.0.1 --port 8001
```

If port 8000 is already used by another project, run `php artisan serve --port=8010` and open that port.

After you change anything in `python_services/`, restart the Python service. It does not reload on its own.

## Making a video

1. Open **Video settings** and choose the voice, theme and quality. Use the voice preview to listen first.
2. Open **Questions**, add a question, then press **Predict output** to run the code and check your answer.
3. Press **Create video**. The progress bar updates while it renders (about 10 to 30 seconds, depending on quality).
4. Open **Videos** to watch or download the MP4.

Typical result: about 45 seconds. At the default settings that is around 1 to 2 MB.

| Quality | Video | Audio | Notes |
| --- | --- | --- | --- |
| Standard | crf 23, fast | 128k | Smallest file |
| High | crf 17 | 192k | Default |
| Max | crf 12, slow | 256k | Best looking, slowest |

## Posting in order

1. Give each question a series (Day or Challenge). The next free number is used if you leave it empty.
2. Open **Upload queue**. It lists everything not yet posted, in posting order: Day 1, Day 2, then the Challenges.
3. Copy the title, description and hashtags, and download the video.
4. Upload it, then press **Mark as uploaded**. You can also save the video link.

## Tests and code style

```bash
composer test
vendor/bin/pint
```

The tests use Pest. They fake the Python service, so it does not need to be running.

## Project layout

```
app/                  Laravel code: models, controllers, form requests, services
config/video.php      languages, series, voices, themes, qualities, defaults
database/             migrations, factories, seeders, SQLite file
resources/views/      Blade pages (questions, categories, queue, videos, settings)
resources/js/         small scripts: predict output, progress polling, copy buttons
python_services/
  video_service/
    main.py           FastAPI endpoints: /health, /predict, /tts-preview, /render
    renderer.py       timeline, narration, ffmpeg encoding, quality presets
    frames.py         draws every frame with Pillow
    languages.py      syntax highlighting rules per language
    predictor.py      runs code in a subprocess with a 5 second limit
    tts.py, audio.py  voices and sound effects
    themes.py         colour themes
    db.py             writes progress into the shared database
tests/                Pest feature tests
```

## How rendering works

1. Laravel creates a `generated_videos` row and sends the question and settings to the Python service (`POST /render`, with the `X-Api-Token` header).
2. Python answers at once and renders in the background, one video at a time.
3. While rendering it writes status, progress and a message into the same row. The page polls Laravel for it.
4. The finished MP4 goes into Laravel's storage. Laravel serves it for playing and downloading.

## Troubleshooting

| Problem | Fix |
| --- | --- |
| "The Python service is not running" | Start it (`composer run dev`) and check `VIDEO_PYTHON_URL` |
| Python service does not start with `composer run dev` | Make sure `python_services/.venv` exists and the packages are installed |
| 401 or "unauthorized" from Python | `VIDEO_PYTHON_TOKEN` and `API_TOKEN` do not match |
| New language, quality or Day badge missing in the video | The Python service is still running old code. Restart it |
| Rendering fails at the voice step | Edge TTS needs internet. Or turn narration off in Video settings |
| "Running X is not available here" when predicting | Only Python, JavaScript (needs Node.js) and PHP can be run. Check the answer by hand for the others |
| Page looks unstyled or "Unable to locate file in Vite manifest" | Run `npm run build`, or keep `composer run dev` running |
| Port 8000 already in use | Use `php artisan serve --port=8010` |

## License

Built on [Laravel](https://laravel.com), which is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
