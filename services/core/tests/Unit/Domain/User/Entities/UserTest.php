<?php

declare(strict_types=1);

use App\Domain\User\Entities\User;
use App\Domain\User\Exceptions\UserAlreadySubscribedException;
use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\WeatherStation\ValueObjects\StationId;

test('creates a user with correct data', function () {
    $user = User::create(
        UserId::generate(),
        new Email('john@example.com'),
        'John',
        'Doe',
    );

    expect($user->email()->value())->toBe('john@example.com')
        ->and($user->firstName())->toBe('John')
        ->and($user->lastName())->toBe('Doe')
        ->and($user->id())->toBeInstanceOf(UserId::class)
        ->and($user->subscriptions())->toBe([]);
});

test('updates user data', function () {
    $user = User::create(
        UserId::generate(),
        new Email('john@example.com'),
        'John',
        'Doe',
    );

    $user->update(new Email('jane@example.com'), 'Jane', 'Smith');

    expect($user->email()->value())->toBe('jane@example.com')
        ->and($user->firstName())->toBe('Jane')
        ->and($user->lastName())->toBe('Smith');
});

test('id does not change after update', function () {
    $id = UserId::generate();
    $user = User::create($id, new Email('john@example.com'), 'John', 'Doe');

    $user->update(new Email('jane@example.com'), 'Jane', 'Smith');

    expect($user->id()->equals($id))->toBeTrue();
});

test('subscribes user to a station', function () {
    $user      = User::create(UserId::generate(), new Email('john@example.com'), 'John', 'Doe');
    $stationId = StationId::generate();

    $user->subscribe($stationId);

    expect($user->subscriptions())->toHaveCount(1)
        ->and($user->isSubscribedTo($stationId))->toBeTrue();
});

test('throws when subscribing to the same station twice', function () {
    $user      = User::create(UserId::generate(), new Email('john@example.com'), 'John', 'Doe');
    $stationId = StationId::generate();

    $user->subscribe($stationId);
    $user->subscribe($stationId);
})->throws(UserAlreadySubscribedException::class);

test('unsubscribes user from a station', function () {
    $stationId = StationId::generate();
    $user      = User::create(UserId::generate(), new Email('john@example.com'), 'John', 'Doe', [$stationId]);

    $user->unsubscribe($stationId);

    expect($user->subscriptions())->toBe([])
        ->and($user->isSubscribedTo($stationId))->toBeFalse();
});

test('unsubscribe is silent when station was not subscribed', function () {
    $user = User::create(UserId::generate(), new Email('john@example.com'), 'John', 'Doe');

    $user->unsubscribe(StationId::generate());

    expect($user->subscriptions())->toBe([]);
});