<?php

declare(strict_types=1);

use App\Application\Messaging\RawMeasurementHandler;
use Illuminate\Support\Facades\Log;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\SDK\Trace\SpanExporter\InMemorySpanExporterFactory;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use Tests\TestCase;
use Tests\Unit\Domain\Measurement\FakeMeasurementRepository;
use Tests\Unit\Infrastructure\Messaging\FakeEventPublisher;

uses(TestCase::class);

beforeEach(fn () => Log::spy());

function rawMeasurementHandlerTracer(): TracerInterface
{
    return Globals::tracerProvider()->getTracer('test');
}

function rawMeasurementHandlerInMemoryTracer(): TracerInterface
{
    return TracerProvider::builder()
        ->addSpanProcessor(new SimpleSpanProcessor((new InMemorySpanExporterFactory())->create()))
        ->build()
        ->getTracer('test');
}

function makeRawMeasurementHandler(
    FakeMeasurementRepository $repository,
    FakeEventPublisher $publisher,
): RawMeasurementHandler {
    return new RawMeasurementHandler($repository, $publisher, rawMeasurementHandlerTracer(), 'alert-events');
}

function makeRawMeasurementPayload(
    string $stationId = '00000000-0000-4000-a000-000000000001',
    string $stationName = 'Universidad Nacional de Quilmes',
    float $temperature = 20.0,
    float $humidity = 50.0,
    float $atmosphericPressure = 1013.0,
    string $traceId = 'ingest-test-001',
): array {
    return [
        'event'                => 'RawMeasurementIngested',
        'station_id'           => $stationId,
        'station_name'         => $stationName,
        'provider'             => 'openweather',
        'temperature'          => $temperature,
        'humidity'             => $humidity,
        'atmospheric_pressure' => $atmosphericPressure,
        'reported_at'          => '2026-04-01T12:00:00Z',
        'trace_id'             => $traceId,
    ];
}

test('persists a measurement from raw payload without station lookup', function () {
    $repository = new FakeMeasurementRepository();
    $handler    = makeRawMeasurementHandler($repository, new FakeEventPublisher());

    $handler->handle(makeRawMeasurementPayload());

    $measurements = $repository->findAll();
    expect($measurements)->toHaveCount(1)
        ->and($measurements[0]->stationId()->value())->toBe('00000000-0000-4000-a000-000000000001')
        ->and($measurements[0]->stationName())->toBe('Universidad Nacional de Quilmes')
        ->and($measurements[0]->temperature()->value())->toBe(20.0)
        ->and($measurements[0]->alertStatus())->toBeFalse();
});

test('calculates extreme heat alert on raw ingestion', function () {
    $repository = new FakeMeasurementRepository();
    $handler    = makeRawMeasurementHandler($repository, new FakeEventPublisher());

    $handler->handle(makeRawMeasurementPayload(temperature: 41.0));

    $measurements = $repository->findAll();
    expect($measurements[0]->alertStatus())->toBeTrue()
        ->and($measurements[0]->alertTypes())->toContain(\App\Domain\Measurement\Enums\AlertType::ExtremeHeat);
});

test('publishes AlertDetected event to alert-events queue when raw measurement has alert', function () {
    $publisher = new FakeEventPublisher();
    $handler   = makeRawMeasurementHandler(new FakeMeasurementRepository(), $publisher);

    $handler->handle(makeRawMeasurementPayload(temperature: 41.0));

    expect($publisher->wasPublishedTo('alert-events'))->toBeTrue();

    $events = $publisher->getPublishedTo('alert-events');
    expect($events)->toHaveCount(1)
        ->and($events[0]['payload']['event'])->toBe('AlertDetected')
        ->and($events[0]['payload']['station_id'])->toBe('00000000-0000-4000-a000-000000000001')
        ->and($events[0]['payload']['station_name'])->toBe('Universidad Nacional de Quilmes')
        ->and($events[0]['payload']['alert_types'])->toContain('extreme_heat');
});

test('AlertDetected payload includes trace_id from active span when otel is enabled', function () {
    config(['services.observability.otel_enabled' => true]);

    $publisher = new FakeEventPublisher();
    $tracer    = rawMeasurementHandlerInMemoryTracer();
    $handler   = new RawMeasurementHandler(
        new FakeMeasurementRepository(),
        $publisher,
        $tracer,
        'alert-events',
    );

    $consumeSpan = $tracer->spanBuilder('consume')->startSpan();
    $scope       = $consumeSpan->activate();

    try {
        $handler->handle(makeRawMeasurementPayload(
            temperature: 41.0,
            traceId: '4bf92f3577b34da6a3ce929d0e0e4736',
        ));

        $events = $publisher->getPublishedTo('alert-events');

        expect($events[0]['payload']['trace_id'])->toBe($consumeSpan->getContext()->getTraceId());
    } finally {
        $scope->detach();
        $consumeSpan->end();
    }
});

test('does not publish to alert-events queue when raw measurement has no alert', function () {
    $publisher = new FakeEventPublisher();
    $handler   = makeRawMeasurementHandler(new FakeMeasurementRepository(), $publisher);

    $handler->handle(makeRawMeasurementPayload(temperature: 20.0));

    expect($publisher->wasPublishedTo('alert-events'))->toBeFalse();
});
