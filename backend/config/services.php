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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'djen' => [
        'base_url' => env(
            'DJEN_BASE_URL',
            'https://comunicaapi.pje.jus.br/api/v1',
        ),

        'timeout' => (int) env(
            'DJEN_TIMEOUT',
            20,
        ),

        'per_page' => (int) env(
            'DJEN_PER_PAGE',
            50,
        ),

        'max_pages_per_sync' => (int) env(
            'DJEN_MAX_PAGES_PER_SYNC',
            100,
        ),

        'lookback_days' => (int) env(
            'DJEN_LOOKBACK_DAYS',
            3,
        ),

        'timezone' => env(
            'DJEN_TIMEZONE',
            'America/Sao_Paulo',
        ),

        'user_agent' => env(
            'DJEN_USER_AGENT',
            'Legalis/1.0',
        ),
    ],

    'datajud' => [
        'base_url' => env(
            'DATAJUD_BASE_URL',
            'https://api-publica.datajud.cnj.jus.br',
        ),

        'api_key' => env('DATAJUD_API_KEY'),

        'timeout' => (int) env('DATAJUD_TIMEOUT', 20),

        'rate_limit_per_minute' => (int) env(
            'DATAJUD_RATE_LIMIT_PER_MINUTE',
            30,
        ),

        'timezone' => env(
            'DATAJUD_TIMEZONE',
            'America/Sao_Paulo',
        ),

        'sync_time' => env('DATAJUD_SYNC_TIME', '04:00'),

        'user_agent' => env(
            'DATAJUD_USER_AGENT',
            'Legalis/1.0',
        ),
    ],

];
