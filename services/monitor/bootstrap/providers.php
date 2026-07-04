<?php

use App\Infrastructure\Metrics\MetricsServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    MetricsServiceProvider::class,
];
