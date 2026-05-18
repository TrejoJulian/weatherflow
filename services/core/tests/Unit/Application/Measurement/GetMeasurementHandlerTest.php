<?php

declare(strict_types=1);

use App\Application\Measurement\GetMeasurement\GetMeasurementHandler;
use App\Application\Measurement\GetMeasurement\GetMeasurementQuery;
use App\Application\Measurement\MeasurementResponse;
use App\Domain\Measurement\Entities\Measurement;
use App\Domain\Measurement\Exceptions\MeasurementNotFoundException;
use App\Domain\Measurement\ValueObjects\AtmosphericPressure;
use App\Domain\Measurement\ValueObjects\Humidity;
use App\Domain\Measurement\ValueObjects\MeasurementId;
use App\Domain\Measurement\ValueObjects\Temperature;
use App\Domain\WeatherStation\ValueObjects\StationId;
use Tests\Unit\Domain\Measurement\FakeMeasurementRepository;

function makeSeededMeasurement(): Measurement
{
    return Measurement::create(
        MeasurementId::generate(),
        StationId::generate(),
        new Temperature(20.0),
        new Humidity(50.0),
        new AtmosphericPressure(1013.0),
        new \DateTimeImmutable('2026-04-01T12:00:00Z'),
    );
}

test('returns a measurement by id', function () {
    $repo = new FakeMeasurementRepository();
    $measurement = makeSeededMeasurement();
    $repo->seed($measurement);

    $handler = new GetMeasurementHandler($repo);
    $response = $handler->handle(new GetMeasurementQuery($measurement->id()->value()));

    expect($response)->toBeInstanceOf(MeasurementResponse::class)
        ->and($response->id)->toBe($measurement->id()->value());
});

test('throws when measurement does not exist', function () {
    $handler = new GetMeasurementHandler(new FakeMeasurementRepository());

    $handler->handle(new GetMeasurementQuery('00000000-0000-4000-a000-000000000000'));
})->throws(MeasurementNotFoundException::class);