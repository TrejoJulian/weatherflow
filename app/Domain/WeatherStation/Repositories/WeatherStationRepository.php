<?php

declare(strict_types=1);

namespace App\Domain\WeatherStation\Repositories;

use App\Domain\WeatherStation\Entities\WeatherStation;
use App\Domain\WeatherStation\ValueObjects\StationId;

interface WeatherStationRepository
{
    public function save(WeatherStation $station): void;

    public function findById(StationId $id): ?WeatherStation;

    public function findAll(): array;

    public function delete(StationId $id): void;
}
