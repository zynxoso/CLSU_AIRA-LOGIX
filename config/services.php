<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'ai' => [
    'budget_threshold' => env('AI_BUDGET_THRESHOLD', 10.0),
    'daily_budget_threshold' => env('AI_DAILY_BUDGET_THRESHOLD', 1.0),
    'minimum_precheck_cost' => env('AI_MINIMUM_PRECHECK_COST', 0.01),
    'prompt_token_cost' => env('AI_PROMPT_TOKEN_COST', 0.000000075),
    'completion_token_cost' => env('AI_COMPLETION_TOKEN_COST', 0.00000030),
    'cache_enabled' => env('AI_CACHE_ENABLED', true),
    'cache_ttl_hours' => env('AI_CACHE_TTL_HOURS', 168), // 7 days
    'cache_version' => env('AI_CACHE_VERSION', 'v1'),
    'max_text_chars' => env('AI_MAX_TEXT_CHARS', 7000),
    'min_fields_to_skip_ai' => env('AI_MIN_FIELDS_TO_SKIP', 6),
    ],

    'ocr' => [
    'local_enabled' => env('LOCAL_OCR_ENABLED', true),
    'binary' => env('TESSERACT_BINARY', 'tesseract'),
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.1-flash-lite-preview'),
    ],

    'neuron' => [
        'key' => env('NEURON_API_KEY'),
        'model' => env('NEURON_MODEL', 'gpt-4o-mini'),
    ],

    'openrouter' => [
        'key' => env('OPENROUTER_API_KEY'),
        'model' => env('OPENROUTER_MODEL', 'google/gemini-3.1-flash-lite-preview-exp:free'),
    ],

];

