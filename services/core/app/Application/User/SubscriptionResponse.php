<?php

declare(strict_types=1);

namespace App\Application\User;

use App\Domain\WeatherStation\Entities\WeatherStation;

final class SubscriptionResponse
{
    public function __construct(
        public readonly string $stationId,
        public readonly string $name,
        public readonly float  $latitude,
        public readonly float  $longitude,
        public readonly string $sensorModel,
        public readonly string $status,
    ) {}

    public static function fromStation(WeatherStation $station): self
    {
        return new self(
            stationId:   $station->id()->value(),
            name:        $station->stationName(),
            latitude:    $station->location()->latitude(),
            longitude:   $station->location()->longitude(),
            sensorModel: $station->sensorModel(),
            status:      $station->status()->value,
        );
    }
}