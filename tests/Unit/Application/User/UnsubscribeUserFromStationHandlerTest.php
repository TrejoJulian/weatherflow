<?php

declare(strict_types=1);

use App\Application\User\UnsubscribeUserFromStation\UnsubscribeUserFromStationCommand;
use App\Application\User\UnsubscribeUserFromStation\UnsubscribeUserFromStationHandler;
use App\Domain\User\Entities\User;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\WeatherStation\ValueObjects\StationId;
use Tests\Unit\Domain\User\FakeUserRepository;

test('unsubscribes user from a station', function () {
    $userRepo  = new FakeUserRepository();
    $stationId = StationId::generate();
    $userId    = UserId::generate();

    $userRepo->seed(User::create($userId, new Email('john@example.com'), 'John', 'Doe', [$stationId]));

    (new UnsubscribeUserFromStationHandler($userRepo))
        ->handle(new UnsubscribeUserFromStationCommand($userId->value(), $stationId->value()));

    $saved = $userRepo->findById($userId);
    expect($saved->isSubscribedTo($stationId))->toBeFalse();
});

test('is silent when user was not subscribed to the station', function () {
    $userRepo = new FakeUserRepository();
    $userId   = UserId::generate();

    $userRepo->seed(User::create($userId, new Email('john@example.com'), 'John', 'Doe'));

    (new UnsubscribeUserFromStationHandler($userRepo))
        ->handle(new UnsubscribeUserFromStationCommand($userId->value(), StationId::generate()->value()));

    $saved = $userRepo->findById($userId);
    expect($saved->subscriptions())->toBe([]);
});

test('throws when user does not exist', function () {
    $userRepo = new FakeUserRepository();

    (new UnsubscribeUserFromStationHandler($userRepo))
        ->handle(new UnsubscribeUserFromStationCommand(UserId::generate()->value(), StationId::generate()->value()));
})->throws(UserNotFoundException::class);