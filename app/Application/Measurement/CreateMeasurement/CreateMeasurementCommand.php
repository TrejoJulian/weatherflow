<?php

declare(strict_types=1);

namespace App\Application\Measurement\CreateMeasurement;

final class CreateMeasurementCommand
{
    public function __construct(
        public readonly string $stationId,
        public readonly float  $temperature,
        public readonly float  $humidity,
        public readonly float  $atmosphericPressure,
        public readonly string $reportedAt,
    ) {}
}