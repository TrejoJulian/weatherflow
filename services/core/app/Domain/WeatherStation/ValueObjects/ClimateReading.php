<?php

declare(strict_types=1);

namespace App\Domain\WeatherStation\ValueObjects;

use DateTimeImmutable;

final class ClimateReading
{
    public function __construct(
        public readonly float             $temperature,
        public readonly float             $humidity,
        public readonly float             $atmosphericPressure,
        public readonly DateTimeImmutable $reportedAt,
    ) {}
}
