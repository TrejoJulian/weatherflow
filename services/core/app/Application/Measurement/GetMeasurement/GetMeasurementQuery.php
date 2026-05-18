<?php

declare(strict_types=1);

namespace App\Application\Measurement\GetMeasurement;

final class GetMeasurementQuery
{
    public function __construct(
        public readonly string $id,
    ) {}
}