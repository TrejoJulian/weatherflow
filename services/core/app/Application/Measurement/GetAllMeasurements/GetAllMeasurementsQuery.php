<?php

declare(strict_types=1);

namespace App\Application\Measurement\GetAllMeasurements;

final class GetAllMeasurementsQuery
{
    public function __construct(
        public readonly ?string $stationName = null,
        public readonly ?float  $tempMin     = null,
        public readonly ?float  $tempMax     = null,
        public readonly ?bool   $alertOnly   = null,
        public readonly ?string $alertType   = null,
    ) {}
}