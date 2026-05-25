<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Measurement;

use App\Domain\Measurement\Entities\Measurement;
use App\Domain\Measurement\Repositories\MeasurementRepository;
use App\Domain\Measurement\ValueObjects\MeasurementFilters;
use App\Domain\Measurement\ValueObjects\MeasurementId;
use App\Domain\WeatherStation\ValueObjects\StationId;
use DateTimeInterface;

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
            fn(Measurement $measurement) => $measurement->stationId()->value() === $stationId->value(),
        ));
    }

    public function updateStationNameByStationId(StationId $stationId, string $newName): void
    {
        foreach ($this->measurements as $measurement) {
            if ($measurement->stationId()->value() === $stationId->value()) {
                $measurement->renameStation($newName);
            }
        }
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
        $reportedAt = $measurement->reportedAt()->format(DateTimeInterface::ATOM);

        return ($filters->stationName() === null || stripos($measurement->stationName(), $filters->stationName()) !== false)
            && ($filters->tempMin()      === null || $measurement->temperature()->value() >= $filters->tempMin())
            && ($filters->tempMax()      === null || $measurement->temperature()->value() <= $filters->tempMax())
            && ($filters->alertOnly()    === null || $measurement->alertStatus() === $filters->alertOnly())
            && ($filters->alertType()    === null || in_array($filters->alertType(), $measurement->alertTypes(), true))
            && ($filters->dateFrom()     === null || $reportedAt >= $filters->dateFrom())
            && ($filters->dateTo()       === null || $reportedAt <= $filters->dateTo())
            && ($filters->humidityMin()  === null || $measurement->humidity()->value() >= $filters->humidityMin())
            && ($filters->humidityMax()  === null || $measurement->humidity()->value() <= $filters->humidityMax())
            && ($filters->pressureMin()  === null || $measurement->atmosphericPressure()->value() >= $filters->pressureMin())
            && ($filters->pressureMax()  === null || $measurement->atmosphericPressure()->value() <= $filters->pressureMax());
    }
}
