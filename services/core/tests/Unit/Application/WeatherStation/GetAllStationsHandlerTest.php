<?php

declare(strict_types=1);

use App\Application\WeatherStation\GetAllStations\GetAllStationsHandler;
use App\Application\WeatherStation\GetAllStations\GetAllStationsQuery;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\WeatherStation\Entities\WeatherStation;
use App\Domain\WeatherStation\ValueObjects\Location;
use App\Domain\WeatherStation\ValueObjects\StationId;
use Tests\Unit\Domain\WeatherStation\FakeWeatherStationRepository;

function makeStationForHandler(
    string             $name      = 'Estación Central',
    string             $createdAt = '2026-04-01T10:00:00+00:00',
): WeatherStation {
    return WeatherStation::create(
        StationId::generate(),
        UserId::fromString('00000000-0000-4000-a000-000000000001'),
        $name,
        new Location(0.0, 0.0),
        'Sensor 1',
        createdAt: new \DateTimeImmutable($createdAt),
    );
}

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

test('filters stations by partial name', function () {
    $repo = new FakeWeatherStationRepository();
    $repo->seed(
        makeStationForHandler(name: 'Estación Central'),
        makeStationForHandler(name: 'Estación Norte'),
    );

    $result = (new GetAllStationsHandler($repo))->handle(new GetAllStationsQuery(name: 'Central'));

    expect($result)->toHaveCount(1)
        ->and($result[0]->stationName)->toBe('Estación Central');
});

test('name filter is case insensitive', function () {
    $repo = new FakeWeatherStationRepository();
    $repo->seed(makeStationForHandler(name: 'Estación Central'));

    $result = (new GetAllStationsHandler($repo))->handle(new GetAllStationsQuery(name: 'central'));

    expect($result)->toHaveCount(1);
});

test('returns empty when name matches no stations', function () {
    $repo = new FakeWeatherStationRepository();
    $repo->seed(makeStationForHandler(name: 'Estación Central'));

    $result = (new GetAllStationsHandler($repo))->handle(new GetAllStationsQuery(name: 'XYZ123'));

    expect($result)->toBeEmpty();
});

test('filters stations by createdFrom', function () {
    $repo = new FakeWeatherStationRepository();
    $repo->seed(
        makeStationForHandler(createdAt: '2026-04-01T10:00:00+00:00'),
        makeStationForHandler(createdAt: '2026-04-10T10:00:00+00:00'),
    );

    $result = (new GetAllStationsHandler($repo))->handle(new GetAllStationsQuery(createdFrom: '2026-04-05T00:00:00+00:00'));

    expect($result)->toHaveCount(1)
        ->and($result[0]->createdAt)->toBe('2026-04-10T10:00:00+00:00');
});

test('filters stations by createdTo', function () {
    $repo = new FakeWeatherStationRepository();
    $repo->seed(
        makeStationForHandler(createdAt: '2026-04-01T10:00:00+00:00'),
        makeStationForHandler(createdAt: '2026-04-10T10:00:00+00:00'),
    );

    $result = (new GetAllStationsHandler($repo))->handle(new GetAllStationsQuery(createdTo: '2026-04-05T00:00:00+00:00'));

    expect($result)->toHaveCount(1)
        ->and($result[0]->createdAt)->toBe('2026-04-01T10:00:00+00:00');
});

test('filters stations by created date range', function () {
    $repo = new FakeWeatherStationRepository();
    $repo->seed(
        makeStationForHandler(createdAt: '2026-04-01T10:00:00+00:00'),
        makeStationForHandler(createdAt: '2026-04-15T10:00:00+00:00'),
        makeStationForHandler(createdAt: '2026-04-30T10:00:00+00:00'),
    );

    $result = (new GetAllStationsHandler($repo))->handle(new GetAllStationsQuery(
        createdFrom: '2026-04-10T00:00:00+00:00',
        createdTo:   '2026-04-20T00:00:00+00:00',
    ));

    expect($result)->toHaveCount(1)
        ->and($result[0]->createdAt)->toBe('2026-04-15T10:00:00+00:00');
});

test('combines name and date filters', function () {
    $repo = new FakeWeatherStationRepository();
    $repo->seed(
        makeStationForHandler(name: 'Estación Central', createdAt: '2026-04-10T10:00:00+00:00'),
        makeStationForHandler(name: 'Estación Central', createdAt: '2026-04-01T10:00:00+00:00'),
        makeStationForHandler(name: 'Estación Norte', createdAt: '2026-04-10T10:00:00+00:00'),
    );

    $result = (new GetAllStationsHandler($repo))->handle(new GetAllStationsQuery(
        name:        'central',
        createdFrom: '2026-04-05T00:00:00+00:00',
    ));

    expect($result)->toHaveCount(1)
        ->and($result[0]->stationName)->toBe('Estación Central')
        ->and($result[0]->createdAt)->toBe('2026-04-10T10:00:00+00:00');
});

test('response includes createdAt', function () {
    $repo = new FakeWeatherStationRepository();
    $repo->seed(makeStationForHandler(createdAt: '2026-04-01T10:00:00+00:00'));

    $result = (new GetAllStationsHandler($repo))->handle(new GetAllStationsQuery());

    expect($result[0]->createdAt)->toBe('2026-04-01T10:00:00+00:00');
});
