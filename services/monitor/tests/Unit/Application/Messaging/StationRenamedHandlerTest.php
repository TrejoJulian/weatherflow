<?php

declare(strict_types=1);

use App\Application\Messaging\StationRenamedHandler;
use App\Domain\Measurement\Entities\Measurement;
use App\Domain\Measurement\ValueObjects\AtmosphericPressure;
use App\Domain\Measurement\ValueObjects\Humidity;
use App\Domain\Measurement\ValueObjects\MeasurementId;
use App\Domain\Measurement\ValueObjects\Temperature;
use App\Domain\WeatherStation\ValueObjects\StationId;
use Tests\Unit\Domain\Measurement\FakeMeasurementRepository;

test('updates station_name for all measurements with the given station_id', function () {
    $stationId    = StationId::fromString('00000000-0000-4000-a000-000000000001');
    $otherStation = StationId::fromString('00000000-0000-4000-a000-000000000002');

    $m1 = Measurement::create(MeasurementId::generate(), $stationId,    'Nombre Original', new Temperature(20.0), new Humidity(50.0), new AtmosphericPressure(1013.0), new DateTimeImmutable('2026-04-01T10:00:00Z'));
    $m2 = Measurement::create(MeasurementId::generate(), $stationId,    'Nombre Original', new Temperature(22.0), new Humidity(55.0), new AtmosphericPressure(1010.0), new DateTimeImmutable('2026-04-01T11:00:00Z'));
    $m3 = Measurement::create(MeasurementId::generate(), $otherStation, 'Otra Estación',   new Temperature(18.0), new Humidity(60.0), new AtmosphericPressure(1005.0), new DateTimeImmutable('2026-04-01T12:00:00Z'));

    $repository = new FakeMeasurementRepository();
    $repository->seed($m1, $m2, $m3);

    (new StationRenamedHandler($repository))->handle([
        'event'      => 'StationRenamed',
        'station_id' => $stationId->value(),
        'new_name'   => 'Nombre Actualizado',
    ]);

    expect($m1->stationName())->toBe('Nombre Actualizado')
        ->and($m2->stationName())->toBe('Nombre Actualizado')
        ->and($m3->stationName())->toBe('Otra Estación');
});

test('does nothing when no measurements match the station_id', function () {
    $repository = new FakeMeasurementRepository();

    (new StationRenamedHandler($repository))->handle([
        'event'      => 'StationRenamed',
        'station_id' => '00000000-0000-4000-a000-000000000099',
        'new_name'   => 'Nombre Actualizado',
    ]);

    expect($repository->findAll())->toBeEmpty();
});
