<?php

use App\Infrastructure\Observability\OpenTelemetryServiceProvider;
use App\Providers\AppServiceProvider;

return [
    OpenTelemetryServiceProvider::class,
    AppServiceProvider::class,
];
