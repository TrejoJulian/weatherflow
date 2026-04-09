<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\WeatherStation;

use App\Domain\WeatherStation\Entities\WeatherStation;
use App\Domain\WeatherStation\Repositories\WeatherStationRepository;
use App\Domain\WeatherStation\ValueObjects\StationId;

final class FakeWeatherStationRepository implements WeatherStationRepository
{
    /** @var WeatherStation[] */
    private array $stations = [];

    public function save(WeatherStation $station): void
    {
        $this->stations[$station->id()->value()] = $station;
    }

    public function findById(StationId $id): ?WeatherStation
    {
        return $this->stations[$id->value()] ?? null;
    }

    public function findAll(): array
    {
        return array_values($this->stations);
    }

    public function delete(StationId $id): void
    {
        unset($this->stations[$id->value()]);
    }

    public function seed(WeatherStation ...$stations): void
    {
        foreach ($stations as $station) {
            $this->stations[$station->id()->value()] = $station;
        }
    }
}