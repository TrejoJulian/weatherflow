<?php

declare(strict_types=1);

namespace App\Application\Messaging;

use App\Application\Contracts\EventPublisher;
use App\Domain\Measurement\Entities\Measurement;
use App\Domain\Measurement\Enums\AlertType;
use App\Domain\Measurement\Repositories\MeasurementRepository;
use App\Domain\Measurement\ValueObjects\AtmosphericPressure;
use App\Domain\Measurement\ValueObjects\Humidity;
use App\Domain\Measurement\ValueObjects\MeasurementId;
use App\Domain\Measurement\ValueObjects\Temperature;
use App\Domain\WeatherStation\ValueObjects\StationId;
use DateTimeImmutable;
use DateTimeInterface;

final class RawMeasurementHandler
{
    public function __construct(
        private readonly MeasurementRepository $measurementRepository,
        private readonly EventPublisher        $eventPublisher,
        private readonly string                $alertsQueue,
    ) {}

    public function handle(array $payload): void
    {
        $measurement = Measurement::create(
            id:                  MeasurementId::generate(),
            stationId:           StationId::fromString($payload['station_id']),
            stationName:         $payload['station_name'],
            temperature:         new Temperature($payload['temperature']),
            humidity:            new Humidity($payload['humidity']),
            atmosphericPressure: new AtmosphericPressure($payload['atmospheric_pressure']),
            reportedAt:          new DateTimeImmutable($payload['reported_at']),
        );

        $this->measurementRepository->save($measurement);

        if ($measurement->alertStatus()) {
            $this->eventPublisher->publish($this->alertsQueue, [
                'event'          => 'AlertDetected',
                'measurement_id' => $measurement->id()->value(),
                'station_id'     => $measurement->stationId()->value(),
                'station_name'   => $payload['station_name'],
                'alert_types'    => array_map(fn (AlertType $type) => $type->value, $measurement->alertTypes()),
                'reported_at'    => $measurement->reportedAt()->format(DateTimeInterface::ATOM),
            ]);
        }
    }
}
