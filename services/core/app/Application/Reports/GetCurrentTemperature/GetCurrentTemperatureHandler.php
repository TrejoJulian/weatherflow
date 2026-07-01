<?php

declare(strict_types=1);

namespace App\Application\Reports\GetCurrentTemperature;

use App\Application\Contracts\LastReadingCache;
use App\Domain\WeatherStation\Entities\WeatherStation;
use App\Domain\WeatherStation\Exceptions\NoCachedReadingAvailableException;
use App\Domain\WeatherStation\Exceptions\StationNotFoundException;
use App\Domain\WeatherStation\Repositories\WeatherStationRepository;
use App\Domain\WeatherStation\ValueObjects\ClimateReading;
use App\Domain\WeatherStation\ValueObjects\StationId;
use App\Infrastructure\Http\Clients\ClimateProviderFactory;
use Throwable;

final class GetCurrentTemperatureHandler
{
    public function __construct(
        private readonly WeatherStationRepository $stationRepository,
        private readonly ClimateProviderFactory   $providerFactory,
        private readonly LastReadingCache          $lastReadingCache,
    ) {}

    public function handle(GetCurrentTemperatureQuery $query): CurrentTemperatureResponse
    {
        $stationId = StationId::fromString($query->stationId);
        $station = $this->stationRepository->findById($stationId);

        if ($station === null) {
            throw new StationNotFoundException($query->stationId);
        }

        $fresh = $this->lastReadingCache->get($stationId);
        if ($fresh !== null) {
            return $this->buildResponse($station, $fresh, stale: false, source: 'cache');
        }

        try {
            $reading = $this->providerFactory
                ->for($station->climateProvider())
                ->fetchCurrentReading($station->location());

            return $this->buildResponse($station, $reading, stale: false, source: 'live');
        } catch (Throwable $exception) {
            $fallback = $this->lastReadingCache->get($stationId, ignoreTtl: true);

            if ($fallback === null) {
                throw new NoCachedReadingAvailableException($query->stationId);
            }

            return $this->buildResponse($station, $fallback, stale: true, source: 'fallback-cache');
        }
    }

    private function buildResponse(
        WeatherStation $station,
        ClimateReading $reading,
        bool $stale,
        string $source,
    ): CurrentTemperatureResponse {
        return new CurrentTemperatureResponse(
            stationId:   $station->id()->value(),
            stationName: $station->stationName(),
            temperature: $reading->temperature,
            reportedAt:  $reading->reportedAt,
            stale:       $stale,
            source:      $source,
        );
    }
}
