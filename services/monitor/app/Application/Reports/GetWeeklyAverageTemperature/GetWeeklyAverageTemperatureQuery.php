<?php

declare(strict_types=1);

namespace App\Application\Reports\GetWeeklyAverageTemperature;

final class GetWeeklyAverageTemperatureQuery
{
    public function __construct(
        public readonly string $stationId,
    ) {}
}
