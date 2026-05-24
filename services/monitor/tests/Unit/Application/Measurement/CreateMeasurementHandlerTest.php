<?php

declare(strict_types=1);

use App\Application\Measurement\CreateMeasurement\CreateMeasurementCommand;
use App\Application\Measurement\CreateMeasurement\CreateMeasurementHandler;
use App\Application\Measurement\MeasurementResponse;
use Tests\Unit\Domain\Measurement\FakeMeasurementRepository;

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
    $handler  = new CreateMeasurementHandler(new FakeMeasurementRepository());
    $response = $handler->handle(makeCreateCommand('00000000-0000-4000-a000-000000000001'));

    expect($response)->toBeInstanceOf(MeasurementResponse::class)
        ->and($response->stationId)->toBe('00000000-0000-4000-a000-000000000001')
        ->and($response->stationName)->toBe('')
        ->and($response->temperature)->toBe(20.0)
        ->and($response->alertStatus)->toBeFalse()
        ->and($response->alertTypes)->toBe(['None']);
});

test('calculates extreme heat alert on creation', function () {
    $handler  = new CreateMeasurementHandler(new FakeMeasurementRepository());
    $response = $handler->handle(makeCreateCommand('00000000-0000-4000-a000-000000000001', temp: 41.0));

    expect($response->alertStatus)->toBeTrue()
        ->and($response->alertTypes)->toBe(['Extreme Heat']);
});

test('calculates multiple alerts simultaneously on creation', function () {
    $handler  = new CreateMeasurementHandler(new FakeMeasurementRepository());
    $response = $handler->handle(makeCreateCommand('00000000-0000-4000-a000-000000000001', temp: -5.0, humidity: 95.0));

    expect($response->alertStatus)->toBeTrue()
        ->and($response->alertTypes)->toContain('Frost')
        ->and($response->alertTypes)->toContain('Critical Humidity');
});
