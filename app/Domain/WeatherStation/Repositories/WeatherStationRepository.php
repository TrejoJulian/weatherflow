<?php

declare(strict_types=1);

namespace App\Domain\WeatherStation\Repositories;

use App\Domain\User\ValueObjects\UserId;
use App\Domain\WeatherStation\Entities\WeatherStation;
use App\Domain\WeatherStation\ValueObjects\StationId;

interface WeatherStationRepository
{
    public function save(WeatherStation $station): void;

    public function findById(StationId $id): ?WeatherStation;

    /** @param StationId[] $ids  @return WeatherStation[] */
    public function findByIds(array $ids): array;

    public function hasStationsOwnedBy(UserId $ownerId): bool;

    public function findAll(): array;

    public function delete(StationId $id): void;
}
