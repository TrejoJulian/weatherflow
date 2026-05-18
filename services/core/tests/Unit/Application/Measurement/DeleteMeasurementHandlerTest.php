<?php

declare(strict_types=1);

use App\Application\Measurement\DeleteMeasurement\DeleteMeasurementCommand;
use App\Application\Measurement\DeleteMeasurement\DeleteMeasurementHandler;
use App\Domain\Measurement\Entities\Measurement;
use App\Domain\Measurement\Exceptions\MeasurementNotFoundException;
use App\Domain\Measurement\ValueObjects\AtmosphericPressure;
use App\Domain\Measurement\ValueObjects\Humidity;
use App\Domain\Measurement\ValueObjects\MeasurementId;
use App\Domain\Measurement\ValueObjects\Temperature;
use App\Domain\WeatherStation\ValueObjects\StationId;
use Tests\Unit\Domain\Measurement\FakeMeasurementRepository;

test('deletes an existing measurement', function () {
    $repo = new FakeMeasurementRepository();
    $measurement = Measurement::create(
        MeasurementId::generate(),
        StationId::generate(),
        new Temperature(20.0),
        new Humidity(50.0),
        new AtmosphericPressure(1013.0),
        new \DateTimeImmutable(),
    );
    $repo->seed($measurement);

    (new DeleteMeasurementHandler($repo))->handle(new DeleteMeasurementCommand($measurement->id()->value()));

    expect($repo->findById($measurement->id()))->toBeNull();
});

test('throws when measurement does not exist', function () {
    (new DeleteMeasurementHandler(new FakeMeasurementRepository()))->handle(
        new DeleteMeasurementCommand('00000000-0000-4000-a000-000000000000'),
    );
})->throws(MeasurementNotFoundException::class);