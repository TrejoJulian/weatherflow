<?php

declare(strict_types=1);

use App\Application\Measurement\CreateMeasurement\CreateMeasurementCommand;
use App\Application\Measurement\CreateMeasurement\CreateMeasurementHandler;
use App\Application\Measurement\MeasurementResponse;
use App\Domain\WeatherStation\Exceptions\StationNotFoundException;
use App\Domain\WeatherStation\StationSummary;
use Tests\Unit\Domain\Measurement\FakeMeasurementRepository;
use Tests\Unit\Infrastructure\Clients\FakeStationClient;
use Tests\Unit\Infrastructure\Messaging\FakeEventPublisher;
use Tests\Unit\Infrastructure\Metrics\FakeMetricsRecorder;

function makeStationSummary(
    string $stationId = '00000000-0000-4000-a000-000000000001',
    string $stationName = 'Central Buenos Aires',
): StationSummary {
    return new StationSummary(stationId: $stationId, stationName: $stationName);
}

function makeCreateCommand(string $stationId, float $temp = 20.0, float $humidity = 50.0, float $pressure = 1013.0): CreateMeasurementCommand
{
    return new CreateMeasurementCommand(
        stationId:           $stationId,
        temperature:         $temp,
        humidity:            $humidity,
        atmosphericPressure: $pressure,
        reportedAt:          '2026-04-01T12:00:00Z',
    );
}

test('creates a measurement and returns a response', function () {
    $stationClient = new FakeStationClient();
    $stationClient->seed(makeStationSummary());

    $metrics  = new FakeMetricsRecorder();
    $handler  = new CreateMeasurementHandler(new FakeMeasurementRepository(), $stationClient, new FakeEventPublisher(), $metrics, 'alert-events');
    $response = $handler->handle(makeCreateCommand('00000000-0000-4000-a000-000000000001'));

    expect($response)->toBeInstanceOf(MeasurementResponse::class)
        ->and($response->stationId)->toBe('00000000-0000-4000-a000-000000000001')
        ->and($response->stationName)->toBe('Central Buenos Aires')
        ->and($response->temperature)->toBe(20.0)
        ->and($response->alertStatus)->toBeFalse()
        ->and($response->alertTypes)->toBe(['None'])
        ->and($metrics->measurementsIngestedCount('manual'))->toBe(1);
});

test('calculates extreme heat alert on creation', function () {
    $stationClient = new FakeStationClient();
    $stationClient->seed(makeStationSummary());

    $handler  = new CreateMeasurementHandler(new FakeMeasurementRepository(), $stationClient, new FakeEventPublisher(), new FakeMetricsRecorder(), 'alert-events');
    $response = $handler->handle(makeCreateCommand('00000000-0000-4000-a000-000000000001', temp: 41.0));

    expect($response->alertStatus)->toBeTrue()
        ->and($response->alertTypes)->toBe(['Extreme Heat']);
});

test('calculates multiple alerts simultaneously on creation', function () {
    $stationClient = new FakeStationClient();
    $stationClient->seed(makeStationSummary());

    $handler  = new CreateMeasurementHandler(new FakeMeasurementRepository(), $stationClient, new FakeEventPublisher(), new FakeMetricsRecorder(), 'alert-events');
    $response = $handler->handle(makeCreateCommand('00000000-0000-4000-a000-000000000001', temp: -5.0, humidity: 95.0));

    expect($response->alertStatus)->toBeTrue()
        ->and($response->alertTypes)->toContain('Frost')
        ->and($response->alertTypes)->toContain('Critical Humidity');
});

test('throws StationNotFoundException when station does not exist', function () {
    $handler = new CreateMeasurementHandler(new FakeMeasurementRepository(), new FakeStationClient(), new FakeEventPublisher(), new FakeMetricsRecorder(), 'alert-events');

    $handler->handle(makeCreateCommand('00000000-0000-4000-a000-000000000099'));
})->throws(StationNotFoundException::class);

test('publishes AlertDetected event to alert-events queue when measurement has alert', function () {
    $stationClient = new FakeStationClient();
    $stationClient->seed(makeStationSummary());
    $publisher = new FakeEventPublisher();

    $handler = new CreateMeasurementHandler(new FakeMeasurementRepository(), $stationClient, $publisher, new FakeMetricsRecorder(), 'alert-events');
    $handler->handle(makeCreateCommand('00000000-0000-4000-a000-000000000001', temp: 41.0));

    expect($publisher->wasPublishedTo('alert-events'))->toBeTrue();

    $events = $publisher->getPublishedTo('alert-events');
    expect($events)->toHaveCount(1)
        ->and($events[0]['payload']['event'])->toBe('AlertDetected')
        ->and($events[0]['payload']['station_id'])->toBe('00000000-0000-4000-a000-000000000001')
        ->and($events[0]['payload']['alert_types'])->toContain('extreme_heat');
});

test('does not publish to alert-events queue when measurement has no alert', function () {
    $stationClient = new FakeStationClient();
    $stationClient->seed(makeStationSummary());
    $publisher = new FakeEventPublisher();

    $handler = new CreateMeasurementHandler(new FakeMeasurementRepository(), $stationClient, $publisher, new FakeMetricsRecorder(), 'alert-events');
    $handler->handle(makeCreateCommand('00000000-0000-4000-a000-000000000001', temp: 20.0));

    expect($publisher->wasPublishedTo('alert-events'))->toBeFalse();
});
