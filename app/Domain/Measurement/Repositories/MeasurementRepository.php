<?php

declare(strict_types=1);

namespace App\Domain\Measurement\Repositories;

use App\Domain\Measurement\Entities\Measurement;
use App\Domain\Measurement\ValueObjects\MeasurementFilters;
use App\Domain\Measurement\ValueObjects\MeasurementId;
use App\Domain\WeatherStation\ValueObjects\StationId;

interface MeasurementRepository
{
    public function save(Measurement $measurement): void;

    public function findById(MeasurementId $id): ?Measurement;

    public function findAll(MeasurementFilters $filters = new MeasurementFilters()): array;

    public function hasMeasurementsForStation(StationId $stationId): bool;

    public function delete(MeasurementId $id): void;
}