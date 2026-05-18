<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MongoDB;

use App\Domain\Measurement\Entities\Measurement;
use App\Domain\Measurement\Enums\AlertType;
use App\Domain\Measurement\Repositories\MeasurementRepository;
use App\Domain\Measurement\ValueObjects\AtmosphericPressure;
use App\Domain\Measurement\ValueObjects\Humidity;
use App\Domain\Measurement\ValueObjects\MeasurementFilters;
use App\Domain\Measurement\ValueObjects\MeasurementId;
use App\Domain\Measurement\ValueObjects\Temperature;
use App\Domain\WeatherStation\ValueObjects\StationId;
use DateTimeImmutable;
use DateTimeInterface;

final class MongoMeasurementRepository implements MeasurementRepository
{
    public function save(Measurement $measurement): void
    {
        MeasurementModel::updateOrCreate(
            ['_id' => $measurement->id()->value()],
            [
                'station_id'           => $measurement->stationId()->value(),
                'temperature'          => $measurement->temperature()->value(),
                'humidity'             => $measurement->humidity()->value(),
                'atmospheric_pressure' => $measurement->atmosphericPressure()->value(),
                'reported_at'          => $measurement->reportedAt()->format(DateTimeInterface::ATOM),
                'alert_status'         => $measurement->alertStatus(),
                'alert_types'          => array_map(fn(AlertType $t) => $t->value, $measurement->alertTypes()),
            ]
        );
    }

    public function findById(MeasurementId $id): ?Measurement
    {
        $model = MeasurementModel::find($id->value());

        return $model ? $this->toDomain($model) : null;
    }

    public function findAll(MeasurementFilters $filters = new MeasurementFilters()): array
    {
        return MeasurementModel::query()
            ->when($filters->stationIds() !== null, fn($query) => $query->whereIn('station_id', $filters->stationIds() ?? []))
            ->when($filters->tempMin()    !== null, fn($query) => $query->where('temperature', '>=', $filters->tempMin()))
            ->when($filters->tempMax()    !== null, fn($query) => $query->where('temperature', '<=', $filters->tempMax()))
            ->when($filters->alertOnly()  !== null, fn($query) => $query->where('alert_status', $filters->alertOnly()))
            ->when($filters->alertType()  !== null, fn($query) => $query->where('alert_types', 'all', [$filters->alertType()->value]))
            ->get()
            ->map(fn(MeasurementModel $model) => $this->toDomain($model))
            ->all();
    }

    public function hasMeasurementsForStation(StationId $stationId): bool
    {
        return MeasurementModel::where('station_id', $stationId->value())->exists();
    }

    public function delete(MeasurementId $id): void
    {
        MeasurementModel::destroy($id->value());
    }

    private function toDomain(MeasurementModel $model): Measurement
    {
        return Measurement::create(
            MeasurementId::fromString($model->_id),
            StationId::fromString($model->station_id),
            new Temperature($model->temperature),
            new Humidity($model->humidity),
            new AtmosphericPressure($model->atmospheric_pressure),
            new DateTimeImmutable($model->reported_at),
        );
    }
}