<?php

declare(strict_types=1);

namespace App\Application\Reports\GetCurrentTemperature;

use App\Domain\WeatherStation\Exceptions\StationNotFoundException;
use App\Domain\WeatherStation\Repositories\WeatherStationRepository;
use App\Domain\WeatherStation\ValueObjects\StationId;
use App\Infrastructure\Http\Clients\ClimateProviderFactory;

final class GetCurrentTemperatureHandler
{
    public function __construct(
        private readonly WeatherStationRepository $stationRepository,
        private readonly ClimateProviderFactory   $providerFactory,
    ) {}

    public function handle(GetCurrentTemperatureQuery $query): CurrentTemperatureResponse
    {
        $station = $this->stationRepository->findById(StationId::fromString($query->stationId));

        if ($station === null) {
            throw new StationNotFoundException($query->stationId);
        }

        $reading = $this->providerFactory
            ->for($station->climateProvider())
            ->fetchCurrentReading($station->location());

        return new CurrentTemperatureResponse(
            stationId:   $station->id()->value(),
            stationName: $station->stationName(),
            temperature: $reading->temperature,
            reportedAt:  $reading->reportedAt,
        );
    }
}
