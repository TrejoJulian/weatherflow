<?php

declare(strict_types=1);

namespace App\Application\IngestMeasurements;

use App\Application\Contracts\EventPublisher;
use App\Application\Contracts\LastReadingCache;
use App\Application\Contracts\MetricsRecorder;
use App\Domain\WeatherStation\Repositories\WeatherStationRepository;
use App\Domain\WeatherStation\ValueObjects\ClimateReading;
use App\Domain\WeatherStation\ValueObjects\StationId;
use App\Infrastructure\Http\Clients\ClimateProviderFactory;
use DateTimeZone;
use Illuminate\Support\Facades\Log;
use Throwable;

final class IngestMeasurementsHandler
{
    public function __construct(
        private readonly WeatherStationRepository $stationRepository,
        private readonly ClimateProviderFactory $providerFactory,
        private readonly EventPublisher $eventPublisher,
        private readonly LastReadingCache $lastReadingCache,
        private readonly MetricsRecorder $metrics,
        private readonly string $rawMeasurementsQueue,
    ) {}

    public function handle(): void
    {
        foreach ($this->stationRepository->findAll() as $station) {
            $traceId = 'ingest-'.bin2hex(random_bytes(8));

            try {
                $provider = $this->providerFactory->for($station->climateProvider());
                $reading = $provider->fetchCurrentReading($station->location());

                $this->cacheLastReading($station->id(), $reading, $traceId);

                $this->eventPublisher->publish($this->rawMeasurementsQueue, [
                    'event' => 'RawMeasurementIngested',
                    'station_id' => $station->id()->value(),
                    'station_name' => $station->stationName(),
                    'provider' => $station->climateProvider()->value,
                    'temperature' => $reading->temperature,
                    'humidity' => $reading->humidity,
                    'atmospheric_pressure' => $reading->atmosphericPressure,
                    'reported_at' => $reading->reportedAt
                        ->setTimezone(new DateTimeZone('UTC'))
                        ->format('Y-m-d\TH:i:s\Z'),
                    'trace_id' => $traceId,
                ]);

                Log::info('Measurement ingested and published', [
                    'station_id' => $station->id()->value(),
                    'trace_id'   => $traceId,
                    'provider'   => $station->climateProvider()->value,
                ]);
            } catch (Throwable $exception) {
                $this->metrics->incrementIngestionError($station->id()->value());

                Log::error('Failed to ingest measurement for station', [
                    'station_id' => $station->id()->value(),
                    'provider' => $station->climateProvider()->value,
                    'trace_id' => $traceId,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    /**
     * Write-through cache of the last successful reading. A cache failure must not
     * abort the ingestion or the message publication, so it is logged and swallowed.
     */
    private function cacheLastReading(StationId $stationId, ClimateReading $reading, string $traceId): void
    {
        try {
            $this->lastReadingCache->put($stationId, $reading);
        } catch (Throwable $cacheException) {
            Log::warning('Failed to cache last reading for station', [
                'station_id' => $stationId->value(),
                'trace_id' => $traceId,
                'error' => $cacheException->getMessage(),
            ]);
        }
    }
}
