<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Prometheus registry storage
    |--------------------------------------------------------------------------
    |
    | php-fpm is multi-process, so the registry is stored in the shared Redis
    | instance (not in-memory/APCu) — otherwise a counter incremented in one
    | worker would be invisible to the /metrics served by another process.
    | Core and Monitor share the same Redis, so a per-service key prefix keeps
    | their metrics from mixing.
    |
    */

    'redis' => [
        'host'     => env('REDIS_HOST', '127.0.0.1'),
        'port'     => (int) env('REDIS_PORT', 6379),
        'password' => env('REDIS_PASSWORD') ?: null,
        'database' => (int) env('PROMETHEUS_REDIS_DB', 0),
    ],

    'prefix' => env('PROMETHEUS_REDIS_PREFIX', 'weatherflow_core'),

    'service_name' => env('OTEL_SERVICE_NAME', 'weatherflow-core'),

];
