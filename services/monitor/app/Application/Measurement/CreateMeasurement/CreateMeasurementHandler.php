<?php

declare(strict_types=1);

namespace App\Application\Measurement\CreateMeasurement;

use App\Application\Contracts\EventPublisher;
use App\Application\Contracts\MetricsRecorder;
use App\Application\Measurement\MeasurementResponse;
use App\Domain\Measurement\Entities\Measurement;
use App\Domain\Measurement\Enums\AlertType;
use App\Domain\Measurement\Repositories\MeasurementRepository;
use App\Domain\Measurement\ValueObjects\AtmosphericPressure;
use App\Domain\Measurement\ValueObjects\Humidity;
use App\Domain\Measurement\ValueObjects\MeasurementId;
use App\Domain\Measurement\ValueObjects\Temperature;
use App\Domain\WeatherStation\Clients\StationClient;
use App\Domain\WeatherStation\Exceptions\StationNotFoundException;
use App\Domain\WeatherStation\ValueObjects\StationId;
use DateTimeImmutable;
use DateTimeInterface;

final class CreateMeasurementHandler
{
    public function __construct(
        private readonly MeasurementRepository $measurementRepository,
        private readonly StationClient         $stationClient,
        private readonly EventPublisher        $eventPublisher,
        private readonly MetricsRecorder       $metrics,
        private readonly string                $alertsQueue,
    ) {}

    public function handle(CreateMeasurementCommand $command): MeasurementResponse
    {
        $stationId = StationId::fromString($command->stationId);

        $stationSummary = $this->stationClient->findById($stationId);

        if ($stationSummary === null) {
            throw new StationNotFoundException($command->stationId);
        }

        $measurement = Measurement::create(
            id:                  MeasurementId::generate(),
            stationId:           $stationId,
            stationName:         $stationSummary->stationName,
            temperature:         new Temperature($command->temperature),
            humidity:            new Humidity($command->humidity),
            atmosphericPressure: new AtmosphericPressure($command->atmosphericPressure),
            reportedAt:          new DateTimeImmutable($command->reportedAt),
        );

        $this->measurementRepository->save($measurement);
        $this->metrics->incrementMeasurementsIngested('manual');

        if ($measurement->alertStatus()) {
            foreach ($measurement->alertTypes() as $alertType) {
                $this->metrics->incrementAlertTriggered($alertType->value);
            }

            $this->eventPublisher->publish($this->alertsQueue, [
                'event'          => 'AlertDetected',
                'measurement_id' => $measurement->id()->value(),
                'station_id'     => $measurement->stationId()->value(),
                'station_name'   => $stationSummary->stationName,
                'alert_types'    => array_map(fn(AlertType $type) => $type->value, $measurement->alertTypes()),
                'reported_at'    => $measurement->reportedAt()->format(DateTimeInterface::ATOM),
            ]);
        }

        return MeasurementResponse::fromEntity($measurement);
    }
}
