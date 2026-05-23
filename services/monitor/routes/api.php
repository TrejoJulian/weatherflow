<?php

declare(strict_types=1);

use App\Infrastructure\Http\Controllers\MeasurementController;
use Illuminate\Support\Facades\Route;

Route::apiResource('measurements', MeasurementController::class);
