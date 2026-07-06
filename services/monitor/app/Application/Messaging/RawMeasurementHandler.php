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
use Illuminate\Support\Facades\Log;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\TracerInterface;

final class RawMeasurementHandler
{
    public function __construct(
        private readonly MeasurementRepository $measurementRepository,
        private readonly EventPublisher $eventPublisher,
        private readonly TracerInterface $tracer,
        private readonly string $alertsQueue,
    ) {}

    public function handle(array $payload): void
    {
        $measurement = Measurement::create(
            id: MeasurementId::generate(),
            stationId: StationId::fromString($payload['station_id']),
            stationName: $payload['station_name'],
            temperature: new Temperature($payload['temperature']),
            humidity: new Humidity($payload['humidity']),
            atmosphericPressure: new AtmosphericPressure($payload['atmospheric_pressure']),
            reportedAt: new DateTimeImmutable($payload['reported_at']),
        );

        $persistSpan = $this->tracer->spanBuilder('measurement.persist')
            ->setAttribute('station_id', $payload['station_id'])
            ->setAttribute('station_name', $payload['station_name'])
            ->startSpan();
        $persistScope = $persistSpan->activate();

        try {
            $this->measurementRepository->save($measurement);

            Log::info('Measurement persisted', [
                'station_id' => $measurement->stationId()->value(),
                'measurement_id' => $measurement->id()->value(),
                'trace_id' => $payload['trace_id'] ?? null,
            ]);
        } finally {
            $persistScope->detach();
            $persistSpan->end();
        }

        if ($measurement->alertStatus()) {
            $traceId = Span::getCurrent()->getContext()->isValid()
                ? Span::getCurrent()->getContext()->getTraceId()
                : ($payload['trace_id'] ?? null);

            $publishSpan = $this->tracer->spanBuilder('alert.publish')
                ->setAttribute('station_id', $payload['station_id'])
                ->startSpan();
            $publishScope = $publishSpan->activate();

            try {
                $this->eventPublisher->publish($this->alertsQueue, [
                    'event' => 'AlertDetected',
                    'measurement_id' => $measurement->id()->value(),
                    'station_id' => $measurement->stationId()->value(),
                    'station_name' => $payload['station_name'],
                    'alert_types' => array_map(fn (AlertType $type) => $type->value, $measurement->alertTypes()),
                    'reported_at' => $measurement->reportedAt()->format(DateTimeInterface::ATOM),
                    'trace_id' => $traceId,
                ]);

                Log::info('AlertDetected published', [
                    'station_id' => $measurement->stationId()->value(),
                    'measurement_id' => $measurement->id()->value(),
                    'alert_types' => array_map(fn (AlertType $type) => $type->value, $measurement->alertTypes()),
                    'trace_id' => $traceId,
                ]);
            } finally {
                $publishScope->detach();
                $publishSpan->end();
            }
        }
    }
}
