<?php

declare(strict_types=1);

use App\Application\User\DeleteUser\DeleteUserCommand;
use App\Application\User\DeleteUser\DeleteUserHandler;
use App\Domain\User\Entities\User;
use App\Domain\User\Exceptions\UserHasStationsException;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\WeatherStation\Entities\WeatherStation;
use App\Domain\WeatherStation\ValueObjects\Location;
use App\Domain\WeatherStation\ValueObjects\StationId;
use Tests\Unit\Domain\User\FakeUserRepository;
use Tests\Unit\Domain\WeatherStation\FakeWeatherStationRepository;

test('deletes an existing user', function () {
    $id = UserId::generate();
    $userRepo = new FakeUserRepository();
    $userRepo->seed(User::create($id, new Email('john@example.com'), 'John', 'Doe'));

    (new DeleteUserHandler($userRepo, new FakeWeatherStationRepository()))
        ->handle(new DeleteUserCommand($id->value()));

    expect($userRepo->findById($id))->toBeNull();
});

test('throws when user does not exist', function () {
    (new DeleteUserHandler(new FakeUserRepository(), new FakeWeatherStationRepository()))
        ->handle(new DeleteUserCommand(UserId::generate()->value()));
})->throws(UserNotFoundException::class);

test('throws when user owns stations', function () {
    $userId = UserId::generate();
    $userRepo = new FakeUserRepository();
    $userRepo->seed(User::create($userId, new Email('john@example.com'), 'John', 'Doe'));

    $stationRepo = new FakeWeatherStationRepository();
    $stationRepo->seed(WeatherStation::create(
        StationId::generate(),
        $userId,
        'Station Alpha',
        new Location(0.0, 0.0),
        'Sensor X',
    ));

    (new DeleteUserHandler($userRepo, $stationRepo))
        ->handle(new DeleteUserCommand($userId->value()));
})->throws(UserHasStationsException::class);
