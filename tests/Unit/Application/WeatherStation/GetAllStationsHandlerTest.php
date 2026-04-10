<?php

declare(strict_types=1);

use App\Application\WeatherStation\GetAllStations\GetAllStationsHandler;
use App\Application\WeatherStation\GetAllStations\GetAllStationsQuery;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\WeatherStation\Entities\WeatherStation;
use App\Domain\WeatherStation\ValueObjects\Location;
use App\Domain\WeatherStation\ValueObjects\StationId;
use Tests\Unit\Domain\WeatherStation\FakeWeatherStationRepository;

test('returns all stations', function () {
    $repo = new FakeWeatherStationRepository();
    $ownerId = UserId::fromString('00000000-0000-4000-a000-000000000001');
    $repo->seed(
        WeatherStation::create(StationId::generate(), $ownerId, 'Estación A', new Location(0.0, 0.0), 'Sensor 1'),
        WeatherStation::create(StationId::generate(), $ownerId, 'Estación B', new Location(1.0, 1.0), 'Sensor 2'),
    );

    $result = (new GetAllStationsHandler($repo))->handle(new GetAllStationsQuery());

    expect($result)->toHaveCount(2);
});

test('returns empty array when no stations exist', function () {
    $result = (new GetAllStationsHandler(new FakeWeatherStationRepository()))->handle(new GetAllStationsQuery());

    expect($result)->toBeEmpty();
});