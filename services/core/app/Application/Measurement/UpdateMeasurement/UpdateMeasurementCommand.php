<?php

declare(strict_types=1);

namespace App\Application\Measurement\UpdateMeasurement;

final class UpdateMeasurementCommand
{
    public function __construct(
        public readonly string $id,
        public readonly float  $temperature,
        public readonly float  $humidity,
        public readonly float  $atmosphericPressure,
        public readonly string $reportedAt,
    ) {}
}