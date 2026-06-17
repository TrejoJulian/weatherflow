<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\Reports\GetDailyAverageTemperature\GetDailyAverageTemperatureHandler;
use App\Application\Reports\GetDailyAverageTemperature\GetDailyAverageTemperatureQuery;
use App\Application\Reports\GetWeeklyAverageTemperature\GetWeeklyAverageTemperatureHandler;
use App\Application\Reports\GetWeeklyAverageTemperature\GetWeeklyAverageTemperatureQuery;
use App\Infrastructure\Http\Requests\GetAverageTemperatureRequest;
use Illuminate\Http\JsonResponse;

final class ReportController
{
    public function __construct(
        private readonly GetDailyAverageTemperatureHandler  $dailyAverageHandler,
        private readonly GetWeeklyAverageTemperatureHandler $weeklyAverageHandler,
    ) {}

    public function dailyAverage(GetAverageTemperatureRequest $request): JsonResponse
    {
        return response()->json($this->dailyAverageHandler->handle(
            new GetDailyAverageTemperatureQuery($request->input('station_id')),
        ));
    }

    public function weeklyAverage(GetAverageTemperatureRequest $request): JsonResponse
    {
        return response()->json($this->weeklyAverageHandler->handle(
            new GetWeeklyAverageTemperatureQuery($request->input('station_id')),
        ));
    }
}
