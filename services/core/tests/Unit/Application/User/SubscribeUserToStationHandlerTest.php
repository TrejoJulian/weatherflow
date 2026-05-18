<?php

declare(strict_types=1);

use App\Application\User\SubscribeUserToStation\SubscribeUserToStationCommand;
use App\Application\User\SubscribeUserToStation\SubscribeUserToStationHandler;
use App\Application\User\SubscriptionResponse;
use App\Application\User\UserResponseWithSubscriptions;
use App\Domain\User\Entities\User;
use App\Domain\User\Exceptions\UserAlreadySubscribedException;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\WeatherStation\Entities\WeatherStation;
use App\Domain\WeatherStation\Enums\StationStatus;
use App\Domain\WeatherStation\Exceptions\StationNotFoundException;
use App\Domain\WeatherStation\ValueObjects\Location;
use App\Domain\WeatherStation\ValueObjects\StationId;
use Tests\Unit\Domain\User\FakeUserRepository;
use Tests\Unit\Domain\WeatherStation\FakeWeatherStationRepository;

test('subscribes user to a station and returns updated response with station details', function () {
    $userRepo    = new FakeUserRepository();
    $stationRepo = new FakeWeatherStationRepository();

    $userId    = UserId::generate();
    $stationId = StationId::generate();

    $userRepo->seed(User::create($userId, new Email('john@example.com'), 'John', 'Doe'));
    $stationRepo->seed(WeatherStation::create($stationId, UserId::generate(), 'Station Alpha', new Location(0.0, 0.0), 'SensorX', StationStatus::Active));

    $response = (new SubscribeUserToStationHandler($userRepo, $stationRepo))
        ->handle(new SubscribeUserToStationCommand($userId->value(), $stationId->value()));

    expect($response)->toBeInstanceOf(UserResponseWithSubscriptions::class)
        ->and($response->subscriptions)->toHaveCount(1)
        ->and($response->subscriptions[0])->toBeInstanceOf(SubscriptionResponse::class)
        ->and($response->subscriptions[0]->stationId)->toBe($stationId->value())
        ->and($response->subscriptions[0]->name)->toBe('Station Alpha');
});

test('throws when user does not exist', function () {
    $userRepo    = new FakeUserRepository();
    $stationRepo = new FakeWeatherStationRepository();
    $stationId   = StationId::generate();

    $stationRepo->seed(WeatherStation::create($stationId, UserId::generate(), 'Station Alpha', new Location(0.0, 0.0), 'SensorX', StationStatus::Active));

    (new SubscribeUserToStationHandler($userRepo, $stationRepo))
        ->handle(new SubscribeUserToStationCommand(UserId::generate()->value(), $stationId->value()));
})->throws(UserNotFoundException::class);

test('throws when station does not exist', function () {
    $userRepo    = new FakeUserRepository();
    $stationRepo = new FakeWeatherStationRepository();

    $userId = UserId::generate();
    $userRepo->seed(User::create($userId, new Email('john@example.com'), 'John', 'Doe'));

    (new SubscribeUserToStationHandler($userRepo, $stationRepo))
        ->handle(new SubscribeUserToStationCommand($userId->value(), StationId::generate()->value()));
})->throws(StationNotFoundException::class);

test('throws when user is already subscribed to the station', function () {
    $userRepo    = new FakeUserRepository();
    $stationRepo = new FakeWeatherStationRepository();

    $userId    = UserId::generate();
    $stationId = StationId::generate();

    $userRepo->seed(User::create($userId, new Email('john@example.com'), 'John', 'Doe', [$stationId]));
    $stationRepo->seed(WeatherStation::create($stationId, UserId::generate(), 'Station Alpha', new Location(0.0, 0.0), 'SensorX', StationStatus::Active));

    (new SubscribeUserToStationHandler($userRepo, $stationRepo))
        ->handle(new SubscribeUserToStationCommand($userId->value(), $stationId->value()));
})->throws(UserAlreadySubscribedException::class);