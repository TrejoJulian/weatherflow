<?php

declare(strict_types=1);

use App\Application\Measurement\CreateMeasurement\CreateMeasurementCommand;
use App\Application\Measurement\CreateMeasurement\CreateMeasurementHandler;
use App\Application\Measurement\MeasurementResponse;
use App\Domain\Measurement\Enums\AlertType;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\WeatherStation\Entities\WeatherStation;
use App\Domain\WeatherStation\Enums\StationStatus;
use App\Domain\WeatherStation\Exceptions\StationNotFoundException;
use App\Domain\WeatherStation\ValueObjects\Location;
use App\Domain\WeatherStation\ValueObjects\StationId;
use Tests\Unit\Domain\Measurement\FakeMeasurementRepository;
use Tests\Unit\Domain\WeatherStation\FakeWeatherStationRepository;

function makeStation(): WeatherStation
{
    return WeatherStation::create(
        StationId::generate(),
        UserId::fromString('00000000-0000-4000-a000-000000000001'),
        'Estación Central',
        new Location(-34.6037, -58.3816),
        'Davis Vantage Pro2',
        StationStatus::Active,
    );
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
    $station = makeStation();
    $stationRepo = new FakeWeatherStationRepository();
    $stationRepo->seed($station);

    $handler = new CreateMeasurementHandler(new FakeMeasurementRepository(), $stationRepo);
    $response = $handler->handle(makeCreateCommand($station->id()->value()));

    expect($response)->toBeInstanceOf(MeasurementResponse::class)
        ->and($response->stationId)->toBe($station->id()->value())
        ->and($response->temperature)->toBe(20.0)
        ->and($response->alertStatus)->toBeFalse()
        ->and($response->alertTypes)->toBe(['None']);
});

test('calculates extreme heat alert on creation', function () {
    $station = makeStation();
    $stationRepo = new FakeWeatherStationRepository();
    $stationRepo->seed($station);

    $handler = new CreateMeasurementHandler(new FakeMeasurementRepository(), $stationRepo);
    $response = $handler->handle(makeCreateCommand($station->id()->value(), temp: 41.0));

    expect($response->alertStatus)->toBeTrue()
        ->and($response->alertTypes)->toBe(['Extreme Heat']);
});

test('calculates multiple alerts simultaneously on creation', function () {
    $station = makeStation();
    $stationRepo = new FakeWeatherStationRepository();
    $stationRepo->seed($station);

    $handler = new CreateMeasurementHandler(new FakeMeasurementRepository(), $stationRepo);
    $response = $handler->handle(makeCreateCommand($station->id()->value(), temp: -5.0, humidity: 95.0));

    expect($response->alertStatus)->toBeTrue()
        ->and($response->alertTypes)->toContain('Frost')
        ->and($response->alertTypes)->toContain('Critical Humidity');
});

test('throws when station does not exist', function () {
    $handler = new CreateMeasurementHandler(
        new FakeMeasurementRepository(),
        new FakeWeatherStationRepository(),
    );

    $handler->handle(makeCreateCommand('00000000-0000-4000-a000-000000000000'));
})->throws(StationNotFoundException::class);