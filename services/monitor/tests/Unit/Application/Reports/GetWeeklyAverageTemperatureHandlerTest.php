<?php

declare(strict_types=1);

use App\Application\Reports\GetWeeklyAverageTemperature\GetWeeklyAverageTemperatureHandler;
use App\Application\Reports\GetWeeklyAverageTemperature\GetWeeklyAverageTemperatureQuery;
use App\Domain\Measurement\Entities\Measurement;
use App\Domain\Measurement\ValueObjects\AtmosphericPressure;
use App\Domain\Measurement\ValueObjects\Humidity;
use App\Domain\Measurement\ValueObjects\MeasurementId;
use App\Domain\Measurement\ValueObjects\Temperature;
use App\Domain\WeatherStation\ValueObjects\StationId;
use Tests\Unit\Domain\Measurement\FakeMeasurementRepository;

const WEEKLY_STATION_ID = '00000000-0000-4000-a000-000000000001';
const WEEKLY_OTHER_STATION_ID = '00000000-0000-4000-a000-000000000002';

function makeWeeklyReportMeasurement(
    string $stationId = WEEKLY_STATION_ID,
    float  $temperature = 20.0,
    string $reportedAt = '-2 days',
): Measurement {
    return Measurement::create(
        id:                  MeasurementId::generate(),
        stationId:           StationId::fromString($stationId),
        stationName:         'Test Station',
        temperature:         new Temperature($temperature),
        humidity:            new Humidity(50.0),
        atmosphericPressure: new AtmosphericPressure(1013.0),
        reportedAt:          new DateTimeImmutable($reportedAt),
    );
}

test('returns average temperature for measurements within the last 7 days', function () {
    $repository = new FakeMeasurementRepository();
    $repository->seed(
        makeWeeklyReportMeasurement(temperature: 10.0, reportedAt: '-1 day'),
        makeWeeklyReportMeasurement(temperature: 30.0, reportedAt: '-3 days'),
    );

    $result = (new GetWeeklyAverageTemperatureHandler($repository))->handle(
        new GetWeeklyAverageTemperatureQuery(WEEKLY_STATION_ID),
    );

    expect($result->stationId)->toBe(WEEKLY_STATION_ID)
        ->and($result->window)->toBe('week')
        ->and($result->averageTemperature)->toBe(20.0)
        ->and($result->message)->toBeNull();
});

test('returns null average and message when no measurements exist in window', function () {
    $result = (new GetWeeklyAverageTemperatureHandler(new FakeMeasurementRepository()))->handle(
        new GetWeeklyAverageTemperatureQuery(WEEKLY_STATION_ID),
    );

    expect($result->averageTemperature)->toBeNull()
        ->and($result->message)->toBe('No measurements found for this station in the requested time window.');
});

test('ignores measurements from other stations', function () {
    $repository = new FakeMeasurementRepository();
    $repository->seed(
        makeWeeklyReportMeasurement(stationId: WEEKLY_STATION_ID, temperature: 20.0),
        makeWeeklyReportMeasurement(stationId: WEEKLY_OTHER_STATION_ID, temperature: 40.0),
    );

    $result = (new GetWeeklyAverageTemperatureHandler($repository))->handle(
        new GetWeeklyAverageTemperatureQuery(WEEKLY_STATION_ID),
    );

    expect($result->averageTemperature)->toBe(20.0);
});

test('ignores measurements outside the 7 day window', function () {
    $repository = new FakeMeasurementRepository();
    $repository->seed(
        makeWeeklyReportMeasurement(temperature: 20.0, reportedAt: '-2 days'),
        makeWeeklyReportMeasurement(temperature: 40.0, reportedAt: '-10 days'),
    );

    $result = (new GetWeeklyAverageTemperatureHandler($repository))->handle(
        new GetWeeklyAverageTemperatureQuery(WEEKLY_STATION_ID),
    );

    expect($result->averageTemperature)->toBe(20.0);
});

test('includes from and to timestamps in the response', function () {
    $result = (new GetWeeklyAverageTemperatureHandler(new FakeMeasurementRepository()))->handle(
        new GetWeeklyAverageTemperatureQuery(WEEKLY_STATION_ID),
    );

    expect($result->from)->not->toBeEmpty()
        ->and($result->to)->not->toBeEmpty();
});
