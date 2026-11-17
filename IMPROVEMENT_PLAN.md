# Improvement plan: better hook, more engagement, more natural voice

Nothing in this plan is implemented yet. It is based on the first YouTube analytics (23 views in about 2 hours) and on the feedback that the voice sounds robotic.

## What the analytics told us

| Metric | Result | Meaning |
| --- | --- | --- |
| Views | 23 (first ~2 hours) | Far too early to judge. Shorts often need 24 to 48 hours. |
| Stayed vs swiped away | 42.9% stayed | The first 1 to 2 seconds are the weak spot. |
| Average view duration | 1:45 on a 47 s video (225%) | Viewers replay it. Strong signal, keep the format. |
| Likes, comments | 0 | Nothing in the video asks for a comment. |

**Rule:** do not change the format based on one video. Post 5 to 10 videos with the new version and compare the "stayed" percentage and comments.

## Goals

1. Stop more people from swiping away in the first 2 seconds.
2. Get comments (people typing A, B, C or D).
3. Make the voice sound more natural.
4. Shorten the video from about 46 s to about 30 to 35 s.

## Changes

### 1. Voice: newer, more natural voices (free)

- Add the newer Edge "Multilingual" voices to `voices` in `config/video.php`:
  - `en-US-AndrewMultilingualNeural`
  - `en-US-AvaMultilingualNeural`
  - `en-US-BrianMultilingualNeural`
  - `en-US-EmmaMultilingualNeural`
- Make one of them the default voice (choose after listening with the voice preview).
- Check that each voice works with the installed `edge-tts` version. Upgrade `edge-tts` if a voice is missing (needs approval, because it changes a dependency).
- Try a speech rate of about +5% to +10%.

### 2. Voice: better pacing (free)

In `python_services/video_service/renderer.py`, in `narration_script()`:

- Read the options with a short pause between them instead of one flat sentence.
- Make the question line shorter and punchier.
- Remove the series intro ("Python quiz. Day 5.") from the start of the narration, because it delays the hook. The Day badge stays on screen.
- Keep the explanation at a calmer pace than the question.

### 3. Hook in the first 2 seconds

- Start straight on the code with a bold challenge line on screen and in the narration, for example:
  - "Can you solve this in 5 seconds?"
  - "99% get this wrong"
  - "What does this print?"
- Pick a hook from a small list so videos do not all start the same way (rotate, or let the question store its own hook text).
- Changes: `frames.py` (question scene) and `renderer.py` (narration).

### 4. Comment prompt

- During the thinking countdown, show on-screen text such as "Comment A, B, C or D!" and say it once.
- Optionally add a "pin this comment" line to the upload description or the upload queue page, with a ready-made pinned comment to copy ("What's your answer? A, B, C or D?").
- Changes: `frames.py` (think scene), `renderer.py` (narration), `UploadMetadata` (pinned comment text), upload queue view (copy button).

### 5. Shorter video

- Default think time from 8 s to 5 s (`config/video.php` defaults, per-question override still works).
- Shorten the outro from 2.5 s to about 1.5 s.
- Target length: 30 to 35 s.

### 6. Settings so old and new can be compared

Add options to Video settings (stored in `video_settings`, sent to Python in the settings payload):

- Hook style: off, or on.
- Comment prompt: off, or on.
- Voice and rate (already exist).

Keep the old behaviour available so videos can be compared with the old one.

## Optional later: a more human voice engine

If the new Edge voices are still not good enough:

| Engine | Cost | Notes |
| --- | --- | --- |
| Piper or Kokoro | Free, runs locally | Good quality. Needs a model download and a new engine class. |
| OpenAI TTS or ElevenLabs | Paid per character, a few cents per video | Closest to human. Needs an API key in `.env` and an engine choice in settings. |

Design note: put engines behind one small interface in `tts.py` (text, voice, rate to audio) so a new engine does not touch the renderer.

## Files likely to change

| File | Change |
| --- | --- |
| `config/video.php` | New voices, new defaults (think time, hook, comment prompt) |
| `python_services/video_service/renderer.py` | Narration script, timeline lengths |
| `python_services/video_service/frames.py` | Hook text, comment prompt text |
| `python_services/video_service/main.py` | New fields in the settings payload |
| `database/migrations/...` | New columns on `video_settings` (hook, comment prompt) |
| `app/Models/VideoSetting.php`, `UpdateVideoSettingRequest.php`, settings view | New options |
| `app/Services/UploadMetadata.php`, upload queue view | Pinned comment text and copy button |
| `tests/Feature/VideoSettingTest.php`, `UploadMetadataTest.php` | Cover the new options |

## Order of work

1. Voices and pacing (items 1 and 2). Listen with the voice preview and choose.
2. Shorter timings (item 5).
3. Hook and comment prompt (items 3 and 4), with the new settings (item 6).
4. Render a few sample videos and watch them.
5. Post 5 to 10 videos, then compare the stayed percentage, average view duration and comments against the first video.

## How to know it worked

- "Stayed" goes up from about 43%.
- Comments appear on most videos.
- Average view duration stays above 100% (the replay effect is kept).

## Open questions

- Which voice sounds best? Decide by listening, since I cannot hear audio.
- Is a paid voice acceptable if the free ones are not enough?
- How long should the countdown be? 5 s is the proposal, but it depends on how hard the questions are.
