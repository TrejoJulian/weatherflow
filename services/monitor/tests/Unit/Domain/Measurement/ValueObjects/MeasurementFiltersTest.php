<?php

declare(strict_types=1);

use App\Domain\Measurement\Entities\Measurement;
use App\Domain\Measurement\ValueObjects\AtmosphericPressure;
use App\Domain\Measurement\ValueObjects\Humidity;
use App\Domain\Measurement\ValueObjects\MeasurementFilters;
use App\Domain\Measurement\ValueObjects\MeasurementId;
use App\Domain\Measurement\ValueObjects\Temperature;
use App\Domain\WeatherStation\ValueObjects\StationId;
use Tests\Unit\Domain\Measurement\FakeMeasurementRepository;

function makeMeasurementForFilters(
    string $stationName = 'Estación Central',
    string $reportedAt  = '2026-04-01T10:00:00+00:00',
    float  $humidity     = 50.0,
    float  $pressure     = 1013.0,
    float  $temp         = 20.0,
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

test('filters measurements by station name case-insensitively', function () {
    $repo = new FakeMeasurementRepository();
    $repo->seed(
        makeMeasurementForFilters(stationName: 'Estación Central'),
        makeMeasurementForFilters(stationName: 'Estación Norte'),
    );

    $results = $repo->findAll(new MeasurementFilters(stationName: 'central'));

    expect($results)->toHaveCount(1)
        ->and($results[0]->stationName())->toBe('Estación Central');
});

test('filters measurements by dateFrom', function () {
    $repo = new FakeMeasurementRepository();
    $repo->seed(
        makeMeasurementForFilters(reportedAt: '2026-04-01T10:00:00+00:00'),
        makeMeasurementForFilters(reportedAt: '2026-04-10T10:00:00+00:00'),
    );

    $results = $repo->findAll(new MeasurementFilters(dateFrom: '2026-04-05T00:00:00+00:00'));

    expect($results)->toHaveCount(1)
        ->and($results[0]->reportedAt()->format('Y-m-d'))->toBe('2026-04-10');
});

test('filters measurements by dateTo', function () {
    $repo = new FakeMeasurementRepository();
    $repo->seed(
        makeMeasurementForFilters(reportedAt: '2026-04-01T10:00:00+00:00'),
        makeMeasurementForFilters(reportedAt: '2026-04-10T10:00:00+00:00'),
    );

    $results = $repo->findAll(new MeasurementFilters(dateTo: '2026-04-05T00:00:00+00:00'));

    expect($results)->toHaveCount(1)
        ->and($results[0]->reportedAt()->format('Y-m-d'))->toBe('2026-04-01');
});

test('filters measurements by date range', function () {
    $repo = new FakeMeasurementRepository();
    $repo->seed(
        makeMeasurementForFilters(reportedAt: '2026-04-01T10:00:00+00:00'),
        makeMeasurementForFilters(reportedAt: '2026-04-15T10:00:00+00:00'),
        makeMeasurementForFilters(reportedAt: '2026-04-30T10:00:00+00:00'),
    );

    $results = $repo->findAll(new MeasurementFilters(
        dateFrom: '2026-04-10T00:00:00+00:00',
        dateTo:   '2026-04-20T00:00:00+00:00',
    ));

    expect($results)->toHaveCount(1)
        ->and($results[0]->reportedAt()->format('Y-m-d'))->toBe('2026-04-15');
});

test('filters measurements by humidityMin', function () {
    $repo = new FakeMeasurementRepository();
    $repo->seed(
        makeMeasurementForFilters(humidity: 40.0),
        makeMeasurementForFilters(humidity: 70.0),
    );

    $results = $repo->findAll(new MeasurementFilters(humidityMin: 50.0));

    expect($results)->toHaveCount(1)
        ->and($results[0]->humidity()->value())->toBe(70.0);
});

test('filters measurements by humidityMax', function () {
    $repo = new FakeMeasurementRepository();
    $repo->seed(
        makeMeasurementForFilters(humidity: 40.0),
        makeMeasurementForFilters(humidity: 70.0),
    );

    $results = $repo->findAll(new MeasurementFilters(humidityMax: 50.0));

    expect($results)->toHaveCount(1)
        ->and($results[0]->humidity()->value())->toBe(40.0);
});

test('filters measurements by humidity range', function () {
    $repo = new FakeMeasurementRepository();
    $repo->seed(
        makeMeasurementForFilters(humidity: 30.0),
        makeMeasurementForFilters(humidity: 50.0),
        makeMeasurementForFilters(humidity: 80.0),
    );

    $results = $repo->findAll(new MeasurementFilters(humidityMin: 40.0, humidityMax: 60.0));

    expect($results)->toHaveCount(1)
        ->and($results[0]->humidity()->value())->toBe(50.0);
});

test('filters measurements by pressureMin', function () {
    $repo = new FakeMeasurementRepository();
    $repo->seed(
        makeMeasurementForFilters(pressure: 1000.0),
        makeMeasurementForFilters(pressure: 1020.0),
    );

    $results = $repo->findAll(new MeasurementFilters(pressureMin: 1010.0));

    expect($results)->toHaveCount(1)
        ->and($results[0]->atmosphericPressure()->value())->toBe(1020.0);
});

test('filters measurements by pressureMax', function () {
    $repo = new FakeMeasurementRepository();
    $repo->seed(
        makeMeasurementForFilters(pressure: 1000.0),
        makeMeasurementForFilters(pressure: 1020.0),
    );

    $results = $repo->findAll(new MeasurementFilters(pressureMax: 1010.0));

    expect($results)->toHaveCount(1)
        ->and($results[0]->atmosphericPressure()->value())->toBe(1000.0);
});

test('filters measurements by pressure range', function () {
    $repo = new FakeMeasurementRepository();
    $repo->seed(
        makeMeasurementForFilters(pressure: 990.0),
        makeMeasurementForFilters(pressure: 1013.0),
        makeMeasurementForFilters(pressure: 1030.0),
    );

    $results = $repo->findAll(new MeasurementFilters(pressureMin: 1000.0, pressureMax: 1020.0));

    expect($results)->toHaveCount(1)
        ->and($results[0]->atmosphericPressure()->value())->toBe(1013.0);
});

test('combines multiple filters with AND logic', function () {
    $repo = new FakeMeasurementRepository();
    $repo->seed(
        makeMeasurementForFilters(
            stationName: 'Estación Central',
            reportedAt:  '2026-04-10T10:00:00+00:00',
            humidity:    70.0,
        ),
        makeMeasurementForFilters(
            stationName: 'Estación Central',
            reportedAt:  '2026-04-01T10:00:00+00:00',
            humidity:    70.0,
        ),
        makeMeasurementForFilters(
            stationName: 'Estación Norte',
            reportedAt:  '2026-04-10T10:00:00+00:00',
            humidity:    70.0,
        ),
        makeMeasurementForFilters(
            stationName: 'Estación Central',
            reportedAt:  '2026-04-10T10:00:00+00:00',
            humidity:    40.0,
        ),
    );

    $results = $repo->findAll(new MeasurementFilters(
        stationName: 'central',
        humidityMin: 60.0,
        dateFrom:    '2026-04-05T00:00:00+00:00',
    ));

    expect($results)->toHaveCount(1)
        ->and($results[0]->stationName())->toBe('Estación Central')
        ->and($results[0]->humidity()->value())->toBe(70.0)
        ->and($results[0]->reportedAt()->format('Y-m-d'))->toBe('2026-04-10');
});
