<?php

use App\Infrastructure\Http\Controllers\MetricsController;
use App\Infrastructure\Http\Middleware\PrometheusHttpMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            // Prometheus scrape endpoint — outside the /api prefix and with no
            // middleware group, exposed at monitor:80/metrics.
            Route::get('/metrics', MetricsController::class);
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Instruments request rate, latency and status per route template.
        $middleware->appendToGroup('api', PrometheusHttpMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
