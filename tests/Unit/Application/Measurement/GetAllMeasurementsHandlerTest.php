<?php

declare(strict_types=1);

use App\Application\Measurement\GetAllMeasurements\GetAllMeasurementsHandler;
use App\Application\Measurement\GetAllMeasurements\GetAllMeasurementsQuery;
use App\Domain\Measurement\Entities\Measurement;
use App\Domain\Measurement\ValueObjects\AtmosphericPressure;
use App\Domain\Measurement\ValueObjects\Humidity;
use App\Domain\Measurement\ValueObjects\MeasurementId;
use App\Domain\Measurement\ValueObjects\Temperature;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\WeatherStation\Entities\WeatherStation;
use App\Domain\WeatherStation\Enums\StationStatus;
use App\Domain\WeatherStation\ValueObjects\Location;
use App\Domain\WeatherStation\ValueObjects\StationId;
use Tests\Unit\Domain\Measurement\FakeMeasurementRepository;
use Tests\Unit\Domain\WeatherStation\FakeWeatherStationRepository;

function makeHandler(FakeMeasurementRepository $measurementRepo, FakeWeatherStationRepository $stationRepo): GetAllMeasurementsHandler
{
    return new GetAllMeasurementsHandler($measurementRepo, $stationRepo);
}

function makeMeasurementForHandler(StationId $stationId, float $temp = 20.0, float $humidity = 50.0, float $pressure = 1013.0): Measurement
{
    return Measurement::create(
        MeasurementId::generate(),
        $stationId,
        new Temperature($temp),
        new Humidity($humidity),
        new AtmosphericPressure($pressure),
        new \DateTimeImmutable(),
    );
}

function makeStationWithName(string $name = 'Estación Central'): WeatherStation
{
    return WeatherStation::create(
        StationId::generate(),
        UserId::generate(),
        $name,
        new Location(-34.6037, -58.3816),
        'Davis Vantage Pro2',
        StationStatus::Active,
    );
}

// -------------------------------------------------------------------------
// No filters
// -------------------------------------------------------------------------

test('returns all measurements when no filters are provided', function () {
    $measurementRepo = new FakeMeasurementRepository();
    $stationId       = StationId::generate();
    $measurementRepo->seed(
        makeMeasurementForHandler($stationId),
        makeMeasurementForHandler($stationId, temp: 30.0),
    );

    $result = makeHandler($measurementRepo, new FakeWeatherStationRepository())
        ->handle(new GetAllMeasurementsQuery());

    expect($result)->toHaveCount(2);
});

test('returns empty array when no measurements exist', function () {
    $result = makeHandler(new FakeMeasurementRepository(), new FakeWeatherStationRepository())
        ->handle(new GetAllMeasurementsQuery());

    expect($result)->toBeEmpty();
});

// -------------------------------------------------------------------------
// Station name filter
// -------------------------------------------------------------------------

test('filters measurements by station name', function () {
    $stationRepo     = new FakeWeatherStationRepository();
    $measurementRepo = new FakeMeasurementRepository();

    $matchingStation = makeStationWithName('Estación Central');
    $otherStation    = makeStationWithName('Estación Norte');
    $stationRepo->seed($matchingStation, $otherStation);

    $measurementRepo->seed(
        makeMeasurementForHandler($matchingStation->id()),
        makeMeasurementForHandler($otherStation->id()),
    );

    $result = makeHandler($measurementRepo, $stationRepo)
        ->handle(new GetAllMeasurementsQuery(stationName: 'Central'));

    expect($result)->toHaveCount(1)
        ->and($result[0]->stationId)->toBe($matchingStation->id()->value());
});

test('returns empty when station name matches no stations', function () {
    $stationRepo = new FakeWeatherStationRepository();
    $stationRepo->seed(makeStationWithName('Estación Central'));

    $measurementRepo = new FakeMeasurementRepository();
    $measurementRepo->seed(makeMeasurementForHandler(StationId::generate()));

    $result = makeHandler($measurementRepo, $stationRepo)
        ->handle(new GetAllMeasurementsQuery(stationName: 'Inexistente'));

    expect($result)->toBeEmpty();
});

// -------------------------------------------------------------------------
// Temperature filters
// -------------------------------------------------------------------------

test('filters measurements by minimum temperature', function () {
    $measurementRepo = new FakeMeasurementRepository();
    $stationId       = StationId::generate();
    $measurementRepo->seed(
        makeMeasurementForHandler($stationId, temp: 10.0),
        makeMeasurementForHandler($stationId, temp: 30.0),
    );

    $result = makeHandler($measurementRepo, new FakeWeatherStationRepository())
        ->handle(new GetAllMeasurementsQuery(tempMin: 20.0));

    expect($result)->toHaveCount(1)
        ->and($result[0]->temperature)->toBe(30.0);
});

test('filters measurements by maximum temperature', function () {
    $measurementRepo = new FakeMeasurementRepository();
    $stationId       = StationId::generate();
    $measurementRepo->seed(
        makeMeasurementForHandler($stationId, temp: 10.0),
        makeMeasurementForHandler($stationId, temp: 30.0),
    );

    $result = makeHandler($measurementRepo, new FakeWeatherStationRepository())
        ->handle(new GetAllMeasurementsQuery(tempMax: 20.0));

    expect($result)->toHaveCount(1)
        ->and($result[0]->temperature)->toBe(10.0);
});

test('filters measurements by temperature range', function () {
    $measurementRepo = new FakeMeasurementRepository();
    $stationId       = StationId::generate();
    $measurementRepo->seed(
        makeMeasurementForHandler($stationId, temp: 5.0),
        makeMeasurementForHandler($stationId, temp: 20.0),
        makeMeasurementForHandler($stationId, temp: 40.0),
    );

    $result = makeHandler($measurementRepo, new FakeWeatherStationRepository())
        ->handle(new GetAllMeasurementsQuery(tempMin: 10.0, tempMax: 35.0));

    expect($result)->toHaveCount(1)
        ->and($result[0]->temperature)->toBe(20.0);
});

// -------------------------------------------------------------------------
// Alert filters
// -------------------------------------------------------------------------

test('filters only alert measurements when alertOnly is true', function () {
    $measurementRepo = new FakeMeasurementRepository();
    $stationId       = StationId::generate();
    $measurementRepo->seed(
        makeMeasurementForHandler($stationId, temp: 20.0),
        makeMeasurementForHandler($stationId, temp: 41.0),
    );

    $result = makeHandler($measurementRepo, new FakeWeatherStationRepository())
        ->handle(new GetAllMeasurementsQuery(alertOnly: true));

    expect($result)->toHaveCount(1)
        ->and($result[0]->alertStatus)->toBeTrue();
});

test('filters only non-alert measurements when alertOnly is false', function () {
    $measurementRepo = new FakeMeasurementRepository();
    $stationId       = StationId::generate();
    $measurementRepo->seed(
        makeMeasurementForHandler($stationId, temp: 20.0),
        makeMeasurementForHandler($stationId, temp: 41.0),
    );

    $result = makeHandler($measurementRepo, new FakeWeatherStationRepository())
        ->handle(new GetAllMeasurementsQuery(alertOnly: false));

    expect($result)->toHaveCount(1)
        ->and($result[0]->alertStatus)->toBeFalse();
});

test('filters measurements by specific alert type', function () {
    $measurementRepo = new FakeMeasurementRepository();
    $stationId       = StationId::generate();
    $measurementRepo->seed(
        makeMeasurementForHandler($stationId, temp: 41.0),
        makeMeasurementForHandler($stationId, temp: -1.0),
    );

    $result = makeHandler($measurementRepo, new FakeWeatherStationRepository())
        ->handle(new GetAllMeasurementsQuery(alertType: 'extreme_heat'));

    expect($result)->toHaveCount(1)
        ->and($result[0]->temperature)->toBe(41.0);
});