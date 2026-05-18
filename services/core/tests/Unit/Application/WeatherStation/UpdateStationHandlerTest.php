<?php

declare(strict_types=1);

use App\Application\WeatherStation\UpdateStation\UpdateStationCommand;
use App\Application\WeatherStation\UpdateStation\UpdateStationHandler;
use App\Domain\User\Entities\User;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\WeatherStation\Entities\WeatherStation;
use App\Domain\WeatherStation\Exceptions\StationNotFoundException;
use App\Domain\WeatherStation\ValueObjects\Location;
use App\Domain\WeatherStation\ValueObjects\StationId;
use Tests\Unit\Domain\User\FakeUserRepository;
use Tests\Unit\Domain\WeatherStation\FakeWeatherStationRepository;

function makeUpdateStationCommand(string $stationId, string $ownerId): UpdateStationCommand
{
    return new UpdateStationCommand(
        id:          $stationId,
        ownerId:     $ownerId,
        stationName: 'Estación Actualizada',
        latitude:    0.0,
        longitude:   0.0,
        sensorModel: 'Sensor Nuevo',
        status:      'inactive',
    );
}

test('updates a station and returns the updated response', function () {
    $user = User::create(UserId::generate(), new Email('owner@example.com'), 'Owner', 'User');
    $station = WeatherStation::create(StationId::generate(), $user->id(), 'Estación Central', new Location(-34.6, -58.3), 'Sensor X');

    $userRepo = new FakeUserRepository();
    $userRepo->seed($user);
    $stationRepo = new FakeWeatherStationRepository();
    $stationRepo->seed($station);

    $response = (new UpdateStationHandler($stationRepo, $userRepo))
        ->handle(makeUpdateStationCommand($station->id()->value(), $user->id()->value()));

    expect($response->stationName)->toBe('Estación Actualizada')
        ->and($response->sensorModel)->toBe('Sensor Nuevo')
        ->and($response->status)->toBe('inactive');
});

test('throws when station does not exist', function () {
    $user = User::create(UserId::generate(), new Email('owner@example.com'), 'Owner', 'User');
    $userRepo = new FakeUserRepository();
    $userRepo->seed($user);

    (new UpdateStationHandler(new FakeWeatherStationRepository(), $userRepo))
        ->handle(makeUpdateStationCommand('00000000-0000-4000-a000-000000000000', $user->id()->value()));
})->throws(StationNotFoundException::class);

test('throws when new owner does not exist', function () {
    $user = User::create(UserId::generate(), new Email('owner@example.com'), 'Owner', 'User');
    $station = WeatherStation::create(StationId::generate(), $user->id(), 'Estación Central', new Location(0.0, 0.0), 'Sensor X');

    $stationRepo = new FakeWeatherStationRepository();
    $stationRepo->seed($station);

    (new UpdateStationHandler($stationRepo, new FakeUserRepository()))
        ->handle(makeUpdateStationCommand($station->id()->value(), '00000000-0000-4000-a000-000000000000'));
})->throws(UserNotFoundException::class);