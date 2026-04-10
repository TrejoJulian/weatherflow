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

test('returns all measurements', function () {
    $repo = new FakeMeasurementRepository();
    $repo->seed(
        Measurement::create(MeasurementId::generate(), StationId::generate(), new Temperature(20.0), new Humidity(50.0), new AtmosphericPressure(1013.0), new \DateTimeImmutable()),
        Measurement::create(MeasurementId::generate(), StationId::generate(), new Temperature(25.0), new Humidity(60.0), new AtmosphericPressure(1010.0), new \DateTimeImmutable()),
    );

    $result = (new GetAllMeasurementsHandler($repo))->handle(new GetAllMeasurementsQuery());

    expect($result)->toHaveCount(2);
});

test('returns empty array when no measurements exist', function () {
    $result = (new GetAllMeasurementsHandler(new FakeMeasurementRepository()))->handle(new GetAllMeasurementsQuery());

    expect($result)->toBeEmpty();
});