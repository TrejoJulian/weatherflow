<?php

declare(strict_types=1);

namespace App\Application\Reports\GetDailyAverageTemperature;

final class GetDailyAverageTemperatureQuery
{
    public function __construct(
        public readonly string $stationId,
    ) {}
}
