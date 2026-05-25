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
    string $reportedAt  = '2026-04-01T10:00:00+00:00',
): Measurement {
    return Measurement::create(
        id:                  MeasurementId::generate(),
        stationId:           StationId::generate(),
        stationName:         $stationName,
        temperature:         new Temperature($temp),
        humidity:            new Humidity($humidity),
        atmosphericPressure: new AtmosphericPressure($pressure),
        reportedAt:          new \DateTimeImmutable($reportedAt),
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
// Date filters
// -------------------------------------------------------------------------

test('filters measurements by dateFrom', function () {
    $repo = new FakeMeasurementRepository();
    $repo->seed(
        makeMeasurementForHandler(reportedAt: '2026-04-01T10:00:00+00:00'),
        makeMeasurementForHandler(reportedAt: '2026-04-10T10:00:00+00:00'),
    );

    $result = (new GetAllMeasurementsHandler($repo))->handle(new GetAllMeasurementsQuery(dateFrom: '2026-04-05T00:00:00+00:00'));

    expect($result)->toHaveCount(1)
        ->and($result[0]->reportedAt)->toBe('2026-04-10T10:00:00+00:00');
});

test('filters measurements by dateTo', function () {
    $repo = new FakeMeasurementRepository();
    $repo->seed(
        makeMeasurementForHandler(reportedAt: '2026-04-01T10:00:00+00:00'),
        makeMeasurementForHandler(reportedAt: '2026-04-10T10:00:00+00:00'),
    );

    $result = (new GetAllMeasurementsHandler($repo))->handle(new GetAllMeasurementsQuery(dateTo: '2026-04-05T00:00:00+00:00'));

    expect($result)->toHaveCount(1)
        ->and($result[0]->reportedAt)->toBe('2026-04-01T10:00:00+00:00');
});

// -------------------------------------------------------------------------
// Humidity filters
// -------------------------------------------------------------------------

test('filters measurements by humidityMin', function () {
    $repo = new FakeMeasurementRepository();
    $repo->seed(
        makeMeasurementForHandler(humidity: 40.0),
        makeMeasurementForHandler(humidity: 70.0),
    );

    $result = (new GetAllMeasurementsHandler($repo))->handle(new GetAllMeasurementsQuery(humidityMin: 50.0));

    expect($result)->toHaveCount(1)
        ->and($result[0]->humidity)->toBe(70.0);
});

test('filters measurements by humidityMax', function () {
    $repo = new FakeMeasurementRepository();
    $repo->seed(
        makeMeasurementForHandler(humidity: 40.0),
        makeMeasurementForHandler(humidity: 70.0),
    );

    $result = (new GetAllMeasurementsHandler($repo))->handle(new GetAllMeasurementsQuery(humidityMax: 50.0));

    expect($result)->toHaveCount(1)
        ->and($result[0]->humidity)->toBe(40.0);
});

// -------------------------------------------------------------------------
// Pressure filters
// -------------------------------------------------------------------------

test('filters measurements by pressureMin', function () {
    $repo = new FakeMeasurementRepository();
    $repo->seed(
        makeMeasurementForHandler(pressure: 1000.0),
        makeMeasurementForHandler(pressure: 1020.0),
    );

    $result = (new GetAllMeasurementsHandler($repo))->handle(new GetAllMeasurementsQuery(pressureMin: 1010.0));

    expect($result)->toHaveCount(1)
        ->and($result[0]->atmosphericPressure)->toBe(1020.0);
});

test('filters measurements by pressureMax', function () {
    $repo = new FakeMeasurementRepository();
    $repo->seed(
        makeMeasurementForHandler(pressure: 1000.0),
        makeMeasurementForHandler(pressure: 1020.0),
    );

    $result = (new GetAllMeasurementsHandler($repo))->handle(new GetAllMeasurementsQuery(pressureMax: 1010.0));

    expect($result)->toHaveCount(1)
        ->and($result[0]->atmosphericPressure)->toBe(1000.0);
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
