<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Python rendering service
    |--------------------------------------------------------------------------
    |
    | Laravel calls this FastAPI service (python_services/) to predict code
    | output and to render the quiz videos. The token is shared with the
    | python_services/.env file.
    |
    */

    'python_url' => env('VIDEO_PYTHON_URL', 'http://127.0.0.1:8001'),

    'python_token' => env('VIDEO_PYTHON_TOKEN', 'change-me'),

    /*
    |--------------------------------------------------------------------------
    | Shared storage
    |--------------------------------------------------------------------------
    |
    | Rendered videos are written by Python straight into this disk and
    | directory (relative to the disk root), so Laravel can stream them.
    |
    */

    'disk' => env('VIDEO_DISK', 'local'),

    'directory' => 'videos',

    /*
    |--------------------------------------------------------------------------
    | Defaults and choices for the settings page
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'voice' => 'en-US-AriaNeural',
        'speech_rate' => 0,
        'speech_pitch' => 0,
        'speech_volume' => 0,
        'narrate_question' => true,
        'narrate_explanation' => true,
        'tick_sound' => true,
        'think_seconds' => 8,
        'theme' => 'midnight',
        'quality' => 'high',
        'channel_name' => 'Python Daily',
    ],

    'voices' => [
        'en-US-AriaNeural' => 'Aria (US, female)',
        'en-US-GuyNeural' => 'Guy (US, male)',
        'en-US-JennyNeural' => 'Jenny (US, female)',
        'en-GB-SoniaNeural' => 'Sonia (UK, female)',
        'en-GB-RyanNeural' => 'Ryan (UK, male)',
        'en-IN-NeerjaNeural' => 'Neerja (India, female)',
        'en-IN-PrabhatNeural' => 'Prabhat (India, male)',
        'en-AU-NatashaNeural' => 'Natasha (Australia, female)',
    ],

    'languages' => [
        'python' => 'Python',
        'javascript' => 'JavaScript',
        'typescript' => 'TypeScript',
        'php' => 'PHP',
        'java' => 'Java',
        'csharp' => 'C#',
        'cpp' => 'C++',
        'go' => 'Go',
        'sql' => 'SQL',
    ],

    /*
    | Numbered series used to post questions in order ("Day 1", "Challenge 3").
    | The order here is the posting order in the upload queue.
    */

    'series' => [
        'day' => 'Day',
        'challenge' => 'Challenge',
    ],

    'qualities' => [
        'standard' => 'Standard (smallest file)',
        'high' => 'High (recommended)',
        'max' => 'Maximum (sharpest, largest file)',
    ],

    'themes' => [
        'midnight' => 'Midnight (dark blue)',
        'ocean' => 'Ocean (teal)',
        'sunset' => 'Sunset (purple and orange)',
        'forest' => 'Forest (green)',
    ],

];
