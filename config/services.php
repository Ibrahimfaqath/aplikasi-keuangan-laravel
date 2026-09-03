<?php

return [

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

    'kiosapi' => [
        'key' => env('KIOSAPI_API_KEY'),
        'url' => env(
            'KIOSAPI_BASE_URL',
            'https://kiosapi.com/v1/chat/completions'
        ),
        'model' => env(
            'KIOSAPI_MODEL',
            'deepseek-v4-flash'
        ),
    ],

    'deepseek' => [
        'key' => env('DEEPSEEK_API_KEY'),
        'url' => env('DEEPSEEK_API_URL', 'https://kiosapi.com/v1/chat/completions'),
        'model' => env('DEEPSEEK_API_MODEL', 'deepseek-chat'),
    ],

];