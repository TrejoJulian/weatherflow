<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Prometheus registry storage
    |--------------------------------------------------------------------------
    |
    | php-fpm is multi-process and the business counters are incremented from
    | the monitor-worker / monitor-worker-raw containers but scraped from the
    | monitor web container. Only a shared Redis storage makes those metrics
    | visible across processes. Core and Monitor share the same Redis, so a
    | per-service key prefix keeps their metrics from mixing.
    |
    */

    'redis' => [
        'host'     => env('REDIS_HOST', '127.0.0.1'),
        'port'     => (int) env('REDIS_PORT', 6379),
        'password' => env('REDIS_PASSWORD') ?: null,
        'database' => (int) env('PROMETHEUS_REDIS_DB', 0),
    ],

    'prefix' => env('PROMETHEUS_REDIS_PREFIX', 'weatherflow_monitor'),

    'service_name' => env('OTEL_SERVICE_NAME', 'weatherflow-monitor'),

];
