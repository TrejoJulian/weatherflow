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

    'core' => [
        'url' => env('CORE_SERVICE_URL', 'http://core/api'),
    ],

    'rabbitmq' => [
        'host'     => env('RABBITMQ_HOST', 'rabbitmq'),
        'port'     => env('RABBITMQ_PORT', 5672),
        'user'     => env('RABBITMQ_USER', 'weatherflow'),
        'password' => env('RABBITMQ_PASSWORD', 'secret'),
    ],

    'queues' => [
        'alerts'           => env('QUEUE_ALERTS', 'alert-events'),
        'stations'         => env('QUEUE_STATIONS', 'station-events'),
        'raw_measurements' => env('QUEUE_RAW_MEASUREMENTS', 'raw-measurements'),
    ],

];
