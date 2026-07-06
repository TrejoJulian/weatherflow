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
use Illuminate\Support\Facades\Log;
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

        $cachedReading = $this->freshCachedReading($stationId);

        if ($cachedReading !== null) {
            return $this->buildResponse($station, $cachedReading, stale: false, source: 'cache');
        }

        try {
            $reading = $this->providerFactory
                ->for($station->climateProvider())
                ->fetchCurrentReading($station->location());

            $this->cacheReading($stationId, $reading);

            return $this->buildResponse($station, $reading, stale: false, source: 'live');
        } catch (Throwable $exception) {
            $fallback = $this->lastReadingCache->get($stationId, ignoreTtl: true);

            if ($fallback === null) {
                throw new NoCachedReadingAvailableException($query->stationId);
            }

            return $this->buildResponse($station, $fallback, stale: true, source: 'fallback-cache');
        }
    }

    /**
     * Fast path: a reading still within its fresh TTL is current enough. A cache
     * failure must not prevent serving live data, so it is treated as a miss.
     */
    private function freshCachedReading(StationId $stationId): ?ClimateReading
    {
        try {
            return $this->lastReadingCache->getFresh($stationId);
        } catch (Throwable $cacheException) {
            Log::warning('Failed to read cached reading for station', [
                'station_id' => $stationId->value(),
                'error' => $cacheException->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Write-through so requests within the TTL are served from cache instead
     * of multiplying traffic to the provider. A cache failure must not fail a
     * request that already holds live data, so it is logged and swallowed.
     */
    private function cacheReading(StationId $stationId, ClimateReading $reading): void
    {
        try {
            $this->lastReadingCache->put($stationId, $reading);
        } catch (Throwable $cacheException) {
            Log::warning('Failed to cache last reading for station', [
                'station_id' => $stationId->value(),
                'error' => $cacheException->getMessage(),
            ]);
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
