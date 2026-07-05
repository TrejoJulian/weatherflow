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
        'host'                => env('RABBITMQ_HOST', 'rabbitmq'),
        'port'                => env('RABBITMQ_PORT', 5672),
        'user'                => env('RABBITMQ_USER', 'weatherflow'),
        'password'            => env('RABBITMQ_PASSWORD', 'secret'),
        'connection_timeout'  => (float) env('RABBITMQ_CONNECTION_TIMEOUT', 3.0),
        'read_write_timeout'  => (float) env('RABBITMQ_READ_WRITE_TIMEOUT', 3.0),
        'heartbeat'                   => (int) env('RABBITMQ_HEARTBEAT', 30),
        'consumer_read_write_timeout' => (float) env('RABBITMQ_CONSUMER_READ_WRITE_TIMEOUT', 60.0),
        'max_retries' => (int) env('RABBITMQ_MAX_RETRIES', 5),
        'retry_delay' => (int) env('RABBITMQ_RETRY_DELAY', 3),
    ],

    'queues' => [
        'alerts'           => env('QUEUE_ALERTS', 'alert-events'),
        'stations'         => env('QUEUE_STATIONS', 'station-events'),
        'raw_measurements' => env('QUEUE_RAW_MEASUREMENTS', 'raw-measurements'),
    ],

    'observability' => [
        'service_name'      => env('OTEL_SERVICE_NAME', 'weatherflow-monitor'),
        'otel_enabled'      => filter_var(env('OTEL_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'otel_exporter_url' => env('OTEL_EXPORTER_OTLP_ENDPOINT', 'http://otel-collector:4318'),
    ],

];
