<?php

declare(strict_types=1);

namespace App\Domain\WeatherStation\Entities;

use App\Domain\User\ValueObjects\UserId;
use App\Domain\WeatherStation\Enums\StationStatus;
use App\Domain\WeatherStation\ValueObjects\Location;
use App\Domain\WeatherStation\ValueObjects\StationId;

final class WeatherStation
{
    private function __construct(
        private readonly StationId $id,
        private UserId             $ownerId,
        private string             $stationName,
        private Location           $location,
        private string             $sensorModel,
        private StationStatus      $status,
    ) {}

    public static function create(
        StationId     $id,
        UserId        $ownerId,
        string        $stationName,
        Location      $location,
        string        $sensorModel,
        StationStatus $status = StationStatus::Active,
    ): self {
        return new self($id, $ownerId, $stationName, $location, $sensorModel, $status);
    }

    public function update(
        UserId        $ownerId,
        string        $stationName,
        Location      $location,
        string        $sensorModel,
        StationStatus $status,
    ): void {
        $this->ownerId     = $ownerId;
        $this->stationName = $stationName;
        $this->location    = $location;
        $this->sensorModel = $sensorModel;
        $this->status      = $status;
    }

    public function id(): StationId
    {
        return $this->id;
    }

    public function ownerId(): UserId
    {
        return $this->ownerId;
    }

    public function stationName(): string
    {
        return $this->stationName;
    }

    public function location(): Location
    {
        return $this->location;
    }

    public function sensorModel(): string
    {
        return $this->sensorModel;
    }

    public function status(): StationStatus
    {
        return $this->status;
    }
}
