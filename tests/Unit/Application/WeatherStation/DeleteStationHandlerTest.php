<?php

declare(strict_types=1);

use App\Application\WeatherStation\DeleteStation\DeleteStationCommand;
use App\Application\WeatherStation\DeleteStation\DeleteStationHandler;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\WeatherStation\Entities\WeatherStation;
use App\Domain\WeatherStation\Exceptions\StationNotFoundException;
use App\Domain\WeatherStation\ValueObjects\Location;
use App\Domain\WeatherStation\ValueObjects\StationId;
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

    (new DeleteStationHandler($repo))->handle(new DeleteStationCommand($station->id()->value()));

    expect($repo->findById($station->id()))->toBeNull();
});

test('throws when station does not exist', function () {
    (new DeleteStationHandler(new FakeWeatherStationRepository()))
        ->handle(new DeleteStationCommand('00000000-0000-4000-a000-000000000000'));
})->throws(StationNotFoundException::class);