<?php

declare(strict_types=1);

use App\Application\Measurement\GetAllMeasurements\GetAllMeasurementsHandler;
use App\Application\Measurement\GetAllMeasurements\GetAllMeasurementsQuery;
use App\Domain\Measurement\Entities\Measurement;
use App\Domain\Measurement\ValueObjects\AtmosphericPressure;
use App\Domain\Measurement\ValueObjects\Humidity;
use App\Domain\Measurement\ValueObjects\MeasurementId;
use App\Domain\Measurement\ValueObjects\Temperature;
use App\Domain\WeatherStation\ValueObjects\StationId;
use Tests\Unit\Domain\Measurement\FakeMeasurementRepository;

function makeMeasurementForHandler(
    string $stationName = 'Estación Central',
    float  $temp        = 20.0,
    float  $humidity    = 50.0,
    float  $pressure    = 1013.0,
): Measurement {
    return Measurement::create(
        id:                  MeasurementId::generate(),
        stationId:           StationId::generate(),
        stationName:         $stationName,
        temperature:         new Temperature($temp),
        humidity:            new Humidity($humidity),
        atmosphericPressure: new AtmosphericPressure($pressure),
        reportedAt:          new \DateTimeImmutable(),
    );
}

// -------------------------------------------------------------------------
// No filters
// -------------------------------------------------------------------------

test('returns all measurements when no filters are provided', function () {
    $repo = new FakeMeasurementRepository();
    $repo->seed(
        makeMeasurementForHandler(),
        makeMeasurementForHandler(temp: 30.0),
    );

    $result = (new GetAllMeasurementsHandler($repo))->handle(new GetAllMeasurementsQuery());

    expect($result)->toHaveCount(2);
});

test('returns empty array when no measurements exist', function () {
    $result = (new GetAllMeasurementsHandler(new FakeMeasurementRepository()))
        ->handle(new GetAllMeasurementsQuery());

    expect($result)->toBeEmpty();
});

// -------------------------------------------------------------------------
// Station name filter
// -------------------------------------------------------------------------

test('filters measurements by station name', function () {
    $repo = new FakeMeasurementRepository();
    $repo->seed(
        makeMeasurementForHandler(stationName: 'Estación Central'),
        makeMeasurementForHandler(stationName: 'Estación Norte'),
    );

    $result = (new GetAllMeasurementsHandler($repo))->handle(new GetAllMeasurementsQuery(stationName: 'Central'));

    expect($result)->toHaveCount(1)
        ->and($result[0]->stationName)->toBe('Estación Central');
});

test('returns empty when station name matches no measurements', function () {
    $repo = new FakeMeasurementRepository();
    $repo->seed(makeMeasurementForHandler(stationName: 'Estación Central'));

    $result = (new GetAllMeasurementsHandler($repo))->handle(new GetAllMeasurementsQuery(stationName: 'Inexistente'));

    expect($result)->toBeEmpty();
});

test('station name filter is case insensitive', function () {
    $repo = new FakeMeasurementRepository();
    $repo->seed(makeMeasurementForHandler(stationName: 'Estación Central'));

    $result = (new GetAllMeasurementsHandler($repo))->handle(new GetAllMeasurementsQuery(stationName: 'central'));

    expect($result)->toHaveCount(1);
});

// -------------------------------------------------------------------------
// Temperature filters
// -------------------------------------------------------------------------

test('filters measurements by minimum temperature', function () {
    $repo = new FakeMeasurementRepository();
    $repo->seed(
        makeMeasurementForHandler(temp: 10.0),
        makeMeasurementForHandler(temp: 30.0),
    );

    $result = (new GetAllMeasurementsHandler($repo))->handle(new GetAllMeasurementsQuery(tempMin: 20.0));

    expect($result)->toHaveCount(1)
        ->and($result[0]->temperature)->toBe(30.0);
});

test('filters measurements by maximum temperature', function () {
    $repo = new FakeMeasurementRepository();
    $repo->seed(
        makeMeasurementForHandler(temp: 10.0),
        makeMeasurementForHandler(temp: 30.0),
    );

    $result = (new GetAllMeasurementsHandler($repo))->handle(new GetAllMeasurementsQuery(tempMax: 20.0));

    expect($result)->toHaveCount(1)
        ->and($result[0]->temperature)->toBe(10.0);
});

test('filters measurements by temperature range', function () {
    $repo = new FakeMeasurementRepository();
    $repo->seed(
        makeMeasurementForHandler(temp: 5.0),
        makeMeasurementForHandler(temp: 20.0),
        makeMeasurementForHandler(temp: 40.0),
    );

    $result = (new GetAllMeasurementsHandler($repo))->handle(new GetAllMeasurementsQuery(tempMin: 10.0, tempMax: 35.0));

    expect($result)->toHaveCount(1)
        ->and($result[0]->temperature)->toBe(20.0);
});

// -------------------------------------------------------------------------
// Alert filters
// -------------------------------------------------------------------------

test('filters only alert measurements when alertOnly is true', function () {
    $repo = new FakeMeasurementRepository();
    $repo->seed(
        makeMeasurementForHandler(temp: 20.0),
        makeMeasurementForHandler(temp: 41.0),
    );

    $result = (new GetAllMeasurementsHandler($repo))->handle(new GetAllMeasurementsQuery(alertOnly: true));

    expect($result)->toHaveCount(1)
        ->and($result[0]->alertStatus)->toBeTrue();
});

test('filters only non-alert measurements when alertOnly is false', function () {
    $repo = new FakeMeasurementRepository();
    $repo->seed(
        makeMeasurementForHandler(temp: 20.0),
        makeMeasurementForHandler(temp: 41.0),
    );

    $result = (new GetAllMeasurementsHandler($repo))->handle(new GetAllMeasurementsQuery(alertOnly: false));

    expect($result)->toHaveCount(1)
        ->and($result[0]->alertStatus)->toBeFalse();
});

test('filters measurements by specific alert type', function () {
    $repo = new FakeMeasurementRepository();
    $repo->seed(
        makeMeasurementForHandler(temp: 41.0),
        makeMeasurementForHandler(temp: -1.0),
    );

    $result = (new GetAllMeasurementsHandler($repo))->handle(new GetAllMeasurementsQuery(alertType: 'extreme_heat'));

    expect($result)->toHaveCount(1)
        ->and($result[0]->temperature)->toBe(41.0);
});
