<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\Reports\GetCurrentTemperature\GetCurrentTemperatureHandler;
use App\Application\Reports\GetCurrentTemperature\GetCurrentTemperatureQuery;
use App\Domain\WeatherStation\Exceptions\StationNotFoundException;
use Illuminate\Http\JsonResponse;

final class ReportController
{
    public function __construct(
        private readonly GetCurrentTemperatureHandler $currentTemperatureHandler,
    ) {}

    public function currentTemperature(string $stationId): JsonResponse
    {
        try {
            return response()->json(
                $this->currentTemperatureHandler->handle(new GetCurrentTemperatureQuery($stationId)),
            );
        } catch (StationNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }
}
