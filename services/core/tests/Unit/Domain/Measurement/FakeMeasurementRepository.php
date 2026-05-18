<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Measurement;

use App\Domain\Measurement\Entities\Measurement;
use App\Domain\Measurement\Repositories\MeasurementRepository;
use App\Domain\Measurement\ValueObjects\MeasurementFilters;
use App\Domain\Measurement\ValueObjects\MeasurementId;
use App\Domain\WeatherStation\ValueObjects\StationId;

final class FakeMeasurementRepository implements MeasurementRepository
{
    /** @var Measurement[] */
    private array $measurements = [];

    public function save(Measurement $measurement): void
    {
        $this->measurements[$measurement->id()->value()] = $measurement;
    }

    public function findById(MeasurementId $id): ?Measurement
    {
        return $this->measurements[$id->value()] ?? null;
    }

    public function findAll(MeasurementFilters $filters = new MeasurementFilters()): array
    {
        return array_values(array_filter(
            $this->measurements,
            fn(Measurement $measurement) => $this->matchesFilters($measurement, $filters),
        ));
    }

    public function hasMeasurementsForStation(StationId $stationId): bool
    {
        return !empty(array_filter(
            $this->measurements,
            fn (Measurement $measurement) => $measurement->stationId()->value() === $stationId->value(),
        ));
    }

    public function delete(MeasurementId $id): void
    {
        unset($this->measurements[$id->value()]);
    }

    public function seed(Measurement ...$measurements): void
    {
        foreach ($measurements as $measurement) {
            $this->measurements[$measurement->id()->value()] = $measurement;
        }
    }

    private function matchesFilters(Measurement $measurement, MeasurementFilters $filters): bool
    {
        return ($filters->stationIds() === null || in_array($measurement->stationId()->value(), $filters->stationIds(), true))
            && ($filters->tempMin()    === null || $measurement->temperature()->value() >= $filters->tempMin())
            && ($filters->tempMax()    === null || $measurement->temperature()->value() <= $filters->tempMax())
            && ($filters->alertOnly()  === null || $measurement->alertStatus() === $filters->alertOnly())
            && ($filters->alertType()  === null || in_array($filters->alertType(), $measurement->alertTypes(), true));
    }
}