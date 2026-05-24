<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MongoDB;

use App\Domain\User\ValueObjects\UserId;
use App\Domain\WeatherStation\Entities\WeatherStation;
use App\Domain\WeatherStation\Enums\StationStatus;
use App\Domain\WeatherStation\Repositories\WeatherStationRepository;
use App\Domain\WeatherStation\ValueObjects\Location;
use App\Domain\WeatherStation\ValueObjects\StationId;

final class MongoWeatherStationRepository implements WeatherStationRepository
{
    public function save(WeatherStation $station): void
    {
        WeatherStationModel::updateOrCreate(
            ['_id' => $station->id()->value()],
            [
                'owner_id'     => $station->ownerId()->value(),
                'name'         => $station->stationName(),
                'location'     => [
                    'latitude'  => $station->location()->latitude(),
                    'longitude' => $station->location()->longitude(),
                ],
                'sensor_model' => $station->sensorModel(),
                'status'       => $station->status()->value,
            ]
        );
    }

    public function findById(StationId $id): ?WeatherStation
    {
        $model = WeatherStationModel::find($id->value());

        return $model ? $this->toDomain($model) : null;
    }

    public function findByIds(array $ids): array
    {
        $stringIds = array_map(fn (StationId $stationId) => $stationId->value(), $ids);

        return WeatherStationModel::whereIn('_id', $stringIds)
            ->get()
            ->map(fn (WeatherStationModel $model) => $this->toDomain($model))
            ->all();
    }

    public function hasStationsOwnedBy(UserId $ownerId): bool
    {
        return WeatherStationModel::where('owner_id', $ownerId->value())->exists();
    }

    public function findAll(): array
    {
        return WeatherStationModel::all()
            ->map(fn (WeatherStationModel $model) => $this->toDomain($model))
            ->all();
    }

    public function delete(StationId $id): void
    {
        WeatherStationModel::destroy($id->value());
    }

    private function toDomain(WeatherStationModel $model): WeatherStation
    {
        return WeatherStation::create(
            StationId::fromString($model->_id),
            UserId::fromString($model->owner_id),
            $model->name,
            new Location($model->location['latitude'], $model->location['longitude']),
            $model->sensor_model,
            StationStatus::from($model->status),
        );
    }
}