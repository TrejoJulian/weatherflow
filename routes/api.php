<?php

declare(strict_types=1);

use App\Infrastructure\Http\Controllers\MeasurementController;
use App\Infrastructure\Http\Controllers\UserController;
use App\Infrastructure\Http\Controllers\WeatherStationController;
use Illuminate\Support\Facades\Route;

Route::apiResource('users', UserController::class);
Route::apiResource('stations', WeatherStationController::class);
Route::apiResource('measurements', MeasurementController::class);