<?php

declare(strict_types=1);

namespace App\Application\WeatherStation\UpdateStation;

final class UpdateStationCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $ownerId,
        public readonly string $stationName,
        public readonly float  $latitude,
        public readonly float  $longitude,
        public readonly string $sensorModel,
        public readonly string $status,
    ) {}
}