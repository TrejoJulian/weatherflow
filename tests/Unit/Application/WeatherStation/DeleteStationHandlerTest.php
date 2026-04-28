<?php

declare(strict_types=1);

use App\Application\WeatherStation\DeleteStation\DeleteStationCommand;
use App\Application\WeatherStation\DeleteStation\DeleteStationHandler;
use App\Domain\Measurement\Entities\Measurement;
use App\Domain\Measurement\ValueObjects\AtmosphericPressure;
use App\Domain\Measurement\ValueObjects\Humidity;
use App\Domain\Measurement\ValueObjects\MeasurementId;
use App\Domain\Measurement\ValueObjects\Temperature;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\WeatherStation\Entities\WeatherStation;
use App\Domain\WeatherStation\Exceptions\StationHasMeasurementsException;
use App\Domain\WeatherStation\Exceptions\StationNotFoundException;
use App\Domain\WeatherStation\ValueObjects\Location;
use App\Domain\WeatherStation\ValueObjects\StationId;
use Tests\Unit\Domain\Measurement\FakeMeasurementRepository;
use Tests\Unit\Domain\WeatherStation\FakeWeatherStationRepository;

test('deletes an existing station', function () {
    $repo = new FakeWeatherStationRepository();
    $station = WeatherStation::create(
        StationId::generate(),
        UserId::fromString('00000000-0000-4000-a000-000000000001'),
        'Estación Central',
        new Location(0.0, 0.0),
        'Sensor X',
    );
    $repo->seed($station);

    (new DeleteStationHandler($repo, new FakeMeasurementRepository()))
        ->handle(new DeleteStationCommand($station->id()->value()));

    expect($repo->findById($station->id()))->toBeNull();
});

test('throws when station does not exist', function () {
    (new DeleteStationHandler(new FakeWeatherStationRepository(), new FakeMeasurementRepository()))
        ->handle(new DeleteStationCommand('00000000-0000-4000-a000-000000000000'));
})->throws(StationNotFoundException::class);

test('throws when station has measurements', function () {
    $stationRepo = new FakeWeatherStationRepository();
    $station = WeatherStation::create(
        StationId::generate(),
        UserId::fromString('00000000-0000-4000-a000-000000000001'),
        'Estación Central',
        new Location(0.0, 0.0),
        'Sensor X',
    );
    $stationRepo->seed($station);

    $measurementRepo = new FakeMeasurementRepository();
    $measurementRepo->seed(Measurement::create(
        MeasurementId::generate(),
        $station->id(),
        new Temperature(25.0),
        new Humidity(60.0),
        new AtmosphericPressure(1013.0),
        new DateTimeImmutable(),
    ));

    (new DeleteStationHandler($stationRepo, $measurementRepo))
        ->handle(new DeleteStationCommand($station->id()->value()));
})->throws(StationHasMeasurementsException::class);
