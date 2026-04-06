<?php

declare(strict_types=1);

use App\Infrastructure\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::apiResource('users', UserController::class);