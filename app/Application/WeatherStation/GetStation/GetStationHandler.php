<?php

declare(strict_types=1);

namespace App\Application\WeatherStation\GetStation;

use App\Application\WeatherStation\StationResponse;
use App\Domain\WeatherStation\Exceptions\StationNotFoundException;
use App\Domain\WeatherStation\Repositories\WeatherStationRepository;
use App\Domain\WeatherStation\ValueObjects\StationId;

final class GetStationHandler
{
    public function __construct(
        private readonly WeatherStationRepository $stationRepository,
    ) {}

    public function handle(GetStationQuery $query): StationResponse
    {
        $station = $this->stationRepository->findById(StationId::fromString($query->id));

        if ($station === null) {
            throw new StationNotFoundException($query->id);
        }

        return StationResponse::fromEntity($station);
    }
}