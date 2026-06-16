<?php

declare(strict_types=1);

use App\Application\Reports\GetDailyAverageTemperature\GetDailyAverageTemperatureHandler;
use App\Application\Reports\GetDailyAverageTemperature\GetDailyAverageTemperatureQuery;
use App\Domain\Measurement\Entities\Measurement;
use App\Domain\Measurement\ValueObjects\AtmosphericPressure;
use App\Domain\Measurement\ValueObjects\Humidity;
use App\Domain\Measurement\ValueObjects\MeasurementId;
use App\Domain\Measurement\ValueObjects\Temperature;
use App\Domain\WeatherStation\ValueObjects\StationId;
use Tests\Unit\Domain\Measurement\FakeMeasurementRepository;

const DAILY_STATION_ID = '00000000-0000-4000-a000-000000000001';
const OTHER_STATION_ID = '00000000-0000-4000-a000-000000000002';

function makeDailyReportMeasurement(
    string $stationId = DAILY_STATION_ID,
    float  $temperature = 20.0,
    string $reportedAt = '-2 hours',
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

test('returns average temperature for measurements within the last 24 hours', function () {
    $repository = new FakeMeasurementRepository();
    $repository->seed(
        makeDailyReportMeasurement(temperature: 10.0),
        makeDailyReportMeasurement(temperature: 30.0),
    );

    $result = (new GetDailyAverageTemperatureHandler($repository))->handle(
        new GetDailyAverageTemperatureQuery(DAILY_STATION_ID),
    );

    expect($result->stationId)->toBe(DAILY_STATION_ID)
        ->and($result->window)->toBe('day')
        ->and($result->averageTemperature)->toBe(20.0)
        ->and($result->message)->toBeNull();
});

test('returns null average and message when no measurements exist in window', function () {
    $result = (new GetDailyAverageTemperatureHandler(new FakeMeasurementRepository()))->handle(
        new GetDailyAverageTemperatureQuery(DAILY_STATION_ID),
    );

    expect($result->averageTemperature)->toBeNull()
        ->and($result->message)->toBe('No measurements found for this station in the requested time window.');
});

test('ignores measurements from other stations', function () {
    $repository = new FakeMeasurementRepository();
    $repository->seed(
        makeDailyReportMeasurement(stationId: DAILY_STATION_ID, temperature: 20.0),
        makeDailyReportMeasurement(stationId: OTHER_STATION_ID, temperature: 40.0),
    );

    $result = (new GetDailyAverageTemperatureHandler($repository))->handle(
        new GetDailyAverageTemperatureQuery(DAILY_STATION_ID),
    );

    expect($result->averageTemperature)->toBe(20.0);
});

test('ignores measurements outside the 24 hour window', function () {
    $repository = new FakeMeasurementRepository();
    $repository->seed(
        makeDailyReportMeasurement(temperature: 20.0, reportedAt: '-2 hours'),
        makeDailyReportMeasurement(temperature: 40.0, reportedAt: '-2 days'),
    );

    $result = (new GetDailyAverageTemperatureHandler($repository))->handle(
        new GetDailyAverageTemperatureQuery(DAILY_STATION_ID),
    );

    expect($result->averageTemperature)->toBe(20.0);
});

test('includes from and to timestamps in the response', function () {
    $result = (new GetDailyAverageTemperatureHandler(new FakeMeasurementRepository()))->handle(
        new GetDailyAverageTemperatureQuery(DAILY_STATION_ID),
    );

    expect($result->from)->not->toBeEmpty()
        ->and($result->to)->not->toBeEmpty();
});
