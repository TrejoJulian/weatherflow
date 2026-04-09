<?php

declare(strict_types=1);

namespace App\Application\WeatherStation\GetAllStations;

use App\Application\WeatherStation\StationResponse;
use App\Domain\WeatherStation\Repositories\WeatherStationRepository;

final class GetAllStationsHandler
{
    public function __construct(
        private readonly WeatherStationRepository $stationRepository,
    ) {}

    /** @return StationResponse[] */
    public function handle(GetAllStationsQuery $query): array
    {
        return array_map(
            fn ($station) => StationResponse::fromEntity($station),
            $this->stationRepository->findAll(),
        );
    }
}