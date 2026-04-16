<?php

declare(strict_types=1);

use App\Domain\Measurement\Enums\AlertType;
use App\Domain\Measurement\ValueObjects\AtmosphericPressure;
use App\Domain\Measurement\ValueObjects\Humidity;
use App\Domain\Measurement\ValueObjects\MeasurementId;
use App\Domain\Measurement\ValueObjects\Temperature;
use App\Domain\Measurement\Entities\Measurement;
use App\Domain\WeatherStation\ValueObjects\StationId;

function makeMeasurement(float $temp = 20.0, float $humidity = 50.0, float $pressure = 1013.0): Measurement
{
    return Measurement::create(
        MeasurementId::generate(),
        StationId::generate(),
        new Temperature($temp),
        new Humidity($humidity),
        new AtmosphericPressure($pressure),
        new \DateTimeImmutable('2026-04-01T12:00:00Z'),
    );
}

test('creates measurement with correct data', function () {
    $measurement = makeMeasurement();

    expect($measurement->temperature()->value())->toBe(20.0)
        ->and($measurement->humidity()->value())->toBe(50.0)
        ->and($measurement->atmosphericPressure()->value())->toBe(1013.0)
        ->and($measurement->id())->toBeInstanceOf(MeasurementId::class);
});

test('has no alert for normal readings', function () {
    $measurement = makeMeasurement();

    expect($measurement->alertStatus())->toBeFalse()
        ->and($measurement->alertTypes())->toBe([AlertType::None]);
});

test('has alert status true when any alert is active', function () {
    $measurement = makeMeasurement(temp: 41.0);

    expect($measurement->alertStatus())->toBeTrue();
});

test('updates measurement and recalculates alerts', function () {
    $measurement = makeMeasurement();

    $measurement->update(
        new Temperature(41.0),
        new Humidity(50.0),
        new AtmosphericPressure(1013.0),
        new \DateTimeImmutable('2026-04-02T12:00:00Z'),
    );

    expect($measurement->temperature()->value())->toBe(41.0)
        ->and($measurement->alertStatus())->toBeTrue()
        ->and($measurement->alertTypes())->toContain(AlertType::ExtremeHeat);
});

test('throws when atmospheric pressure is zero', function () {
    new AtmosphericPressure(0.0);
})->throws(InvalidArgumentException::class);

test('throws when atmospheric pressure is negative', function () {
    new AtmosphericPressure(-10.0);
})->throws(InvalidArgumentException::class);

test('alert clears after update to normal readings', function () {
    $measurement = makeMeasurement(temp: 41.0);

    $measurement->update(
        new Temperature(20.0),
        new Humidity(50.0),
        new AtmosphericPressure(1013.0),
        new \DateTimeImmutable('2026-04-02T12:00:00Z'),
    );

    expect($measurement->alertStatus())->toBeFalse()
        ->and($measurement->alertTypes())->toBe([AlertType::None]);
});