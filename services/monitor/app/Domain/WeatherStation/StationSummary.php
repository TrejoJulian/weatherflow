<?php

declare(strict_types=1);

namespace App\Domain\WeatherStation;

final class StationSummary
{
    public function __construct(
        public readonly string $stationId,
        public readonly string $stationName,
    ) {}
}
