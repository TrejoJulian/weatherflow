<?php

declare(strict_types=1);

namespace App\Application\WeatherStation\GetAllStations;

use App\Application\WeatherStation\StationResponse;
use App\Domain\WeatherStation\Repositories\WeatherStationRepository;
use App\Domain\WeatherStation\ValueObjects\StationFilters;

final class GetAllStationsHandler
{
    public function __construct(
        private readonly WeatherStationRepository $stationRepository,
    ) {}

    /** @return StationResponse[] */
    public function handle(GetAllStationsQuery $query): array
    {
        $filters = new StationFilters(
            name:        $query->name,
            createdFrom: $query->createdFrom,
            createdTo:   $query->createdTo,
        );

        return array_map(
            fn ($station) => StationResponse::fromEntity($station),
            $this->stationRepository->findAll($filters),
        );
    }
}
