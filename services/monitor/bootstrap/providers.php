<?php

use App\Infrastructure\Observability\OpenTelemetryServiceProvider;
use App\Infrastructure\Metrics\MetricsServiceProvider;
use App\Providers\AppServiceProvider;

return [
    OpenTelemetryServiceProvider::class,
    AppServiceProvider::class,
    MetricsServiceProvider::class,
];
