<?php

declare(strict_types=1);

namespace App\Application\IngestMeasurements;

use App\Application\Contracts\EventPublisher;
use App\Domain\WeatherStation\Repositories\WeatherStationRepository;
use App\Infrastructure\Http\Clients\ClimateProviderFactory;
use DateTimeZone;
use Illuminate\Support\Facades\Log;
use Throwable;

final class IngestMeasurementsHandler
{
    public function __construct(
        private readonly WeatherStationRepository $stationRepository,
        private readonly ClimateProviderFactory   $providerFactory,
        private readonly EventPublisher           $eventPublisher,
        private readonly string                   $rawMeasurementsQueue,
    ) {}

    public function handle(): void
    {
        foreach ($this->stationRepository->findAll() as $station) {
            $traceId = 'ingest-' . bin2hex(random_bytes(8));

            try {
                $provider = $this->providerFactory->for($station->climateProvider());
                $reading  = $provider->fetchCurrentReading($station->location());

                $this->eventPublisher->publish($this->rawMeasurementsQueue, [
                    'event'                => 'RawMeasurementIngested',
                    'station_id'           => $station->id()->value(),
                    'station_name'         => $station->stationName(),
                    'provider'             => $station->climateProvider()->value,
                    'temperature'          => $reading->temperature,
                    'humidity'             => $reading->humidity,
                    'atmospheric_pressure' => $reading->atmosphericPressure,
                    'reported_at'          => $reading->reportedAt
                        ->setTimezone(new DateTimeZone('UTC'))
                        ->format('Y-m-d\TH:i:s\Z'),
                    'trace_id'             => $traceId,
                ]);
            } catch (Throwable $exception) {
                Log::error('Failed to ingest measurement for station', [
                    'station_id' => $station->id()->value(),
                    'provider'   => $station->climateProvider()->value,
                    'trace_id'   => $traceId,
                    'error'      => $exception->getMessage(),
                ]);
            }
        }
    }
}
