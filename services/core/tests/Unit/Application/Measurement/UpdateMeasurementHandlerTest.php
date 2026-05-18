<?php

declare(strict_types=1);

use App\Application\Measurement\UpdateMeasurement\UpdateMeasurementCommand;
use App\Application\Measurement\UpdateMeasurement\UpdateMeasurementHandler;
use App\Domain\Measurement\Entities\Measurement;
use App\Domain\Measurement\Exceptions\MeasurementNotFoundException;
use App\Domain\Measurement\ValueObjects\AtmosphericPressure;
use App\Domain\Measurement\ValueObjects\Humidity;
use App\Domain\Measurement\ValueObjects\MeasurementId;
use App\Domain\Measurement\ValueObjects\Temperature;
use App\Domain\WeatherStation\ValueObjects\StationId;
use Tests\Unit\Domain\Measurement\FakeMeasurementRepository;

function makeUpdateCommand(string $id, float $temp = 20.0, float $humidity = 50.0, float $pressure = 1013.0): UpdateMeasurementCommand
{
    return new UpdateMeasurementCommand(
        id:                  $id,
        temperature:         $temp,
        humidity:            $humidity,
        atmosphericPressure: $pressure,
        reportedAt:          '2026-04-02T12:00:00Z',
    );
}

test('updates a measurement and recalculates alerts', function () {
    $repo = new FakeMeasurementRepository();
    $measurement = Measurement::create(
        MeasurementId::generate(),
        StationId::generate(),
        new Temperature(20.0),
        new Humidity(50.0),
        new AtmosphericPressure(1013.0),
        new \DateTimeImmutable('2026-04-01T12:00:00Z'),
    );
    $repo->seed($measurement);

    $response = (new UpdateMeasurementHandler($repo))->handle(
        makeUpdateCommand($measurement->id()->value(), temp: 41.0),
    );

    expect($response->temperature)->toBe(41.0)
        ->and($response->alertStatus)->toBeTrue()
        ->and($response->alertTypes)->toContain('Extreme Heat');
});

test('throws when measurement does not exist', function () {
    (new UpdateMeasurementHandler(new FakeMeasurementRepository()))->handle(
        makeUpdateCommand('00000000-0000-4000-a000-000000000000'),
    );
})->throws(MeasurementNotFoundException::class);