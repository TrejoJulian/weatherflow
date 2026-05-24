<?php

declare(strict_types=1);

namespace App\Application\WeatherStation;

use App\Domain\WeatherStation\Entities\WeatherStation;

final class StationResponse
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

    public static function fromEntity(WeatherStation $station): self
    {
        return new self(
            id:          $station->id()->value(),
            ownerId:     $station->ownerId()->value(),
            stationName: $station->stationName(),
            latitude:    $station->location()->latitude(),
            longitude:   $station->location()->longitude(),
            sensorModel: $station->sensorModel(),
            status:      $station->status()->value,
        );
    }
}
