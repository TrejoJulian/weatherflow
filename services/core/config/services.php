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

    'rabbitmq' => [
        'host'                => env('RABBITMQ_HOST', 'rabbitmq'),
        'port'                => env('RABBITMQ_PORT', 5672),
        'user'                => env('RABBITMQ_USER', 'weatherflow'),
        'password'            => env('RABBITMQ_PASSWORD', 'secret'),
        'connection_timeout'  => (float) env('RABBITMQ_CONNECTION_TIMEOUT', 3.0),
        'read_write_timeout'  => (float) env('RABBITMQ_READ_WRITE_TIMEOUT', 3.0),
    ],

    'resilience' => [
        'owm_connect_timeout' => (int) env('OWM_CONNECT_TIMEOUT', 3),
        'owm_timeout'         => (int) env('OWM_TIMEOUT', 8),
        'owm_retries'         => (int) env('OWM_RETRIES', 3),
        'owm_cache_ttl'       => (int) env('OWM_CACHE_TTL', 600),
        'breaker_threshold'   => (int) env('OWM_BREAKER_THRESHOLD', 5),
        'breaker_reset'       => (int) env('OWM_BREAKER_RESET', 30),
    ],

    'queues' => [
        'stations'         => env('QUEUE_STATIONS', 'station-events'),
        'raw_measurements' => env('QUEUE_RAW_MEASUREMENTS', 'raw-measurements'),
    ],

    'openweather' => [
        'key'      => env('OPENWEATHER_API_KEY'),
        'base_url' => env('OPENWEATHER_BASE_URL', 'https://api.openweathermap.org/data/2.5'),
    ],

    'ingestion' => [
        'cron' => env('INGESTION_CRON', '*/10 * * * *'),
    ],

    'observability' => [
        'service_name'      => env('OTEL_SERVICE_NAME', 'weatherflow-core'),
        'otel_enabled'      => filter_var(env('OTEL_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'otel_exporter_url' => env('OTEL_EXPORTER_OTLP_ENDPOINT', 'http://otel-collector:4318'),
    ],

];
