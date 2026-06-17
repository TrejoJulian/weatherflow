<?php

declare(strict_types=1);

use App\Infrastructure\Http\Controllers\MeasurementController;
use App\Infrastructure\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::apiResource('measurements', MeasurementController::class);

Route::get('reports/avg/day',  [ReportController::class, 'dailyAverage']);
Route::get('reports/avg/week', [ReportController::class, 'weeklyAverage']);
