<?php

declare(strict_types=1);

use App\Application\User\GetUser\GetUserHandler;
use App\Application\User\GetUser\GetUserQuery;
use App\Application\User\UserResponseWithSubscriptions;
use App\Domain\User\Entities\User;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserId;
use Tests\Unit\Domain\User\FakeUserRepository;
use Tests\Unit\Domain\WeatherStation\FakeWeatherStationRepository;

test('returns a user response when user exists', function () {
    $id          = UserId::generate();
    $userRepo    = new FakeUserRepository();
    $stationRepo = new FakeWeatherStationRepository();

    $userRepo->seed(User::create($id, new Email('john@example.com'), 'John', 'Doe'));

    $response = (new GetUserHandler($userRepo, $stationRepo))->handle(new GetUserQuery($id->value()));

    expect($response)->toBeInstanceOf(UserResponseWithSubscriptions::class)
        ->and($response->id)->toBe($id->value())
        ->and($response->email)->toBe('john@example.com')
        ->and($response->firstName)->toBe('John')
        ->and($response->lastName)->toBe('Doe')
        ->and($response->subscriptions)->toBe([]);
});

test('throws when user does not exist', function () {
    $userRepo    = new FakeUserRepository();
    $stationRepo = new FakeWeatherStationRepository();

    (new GetUserHandler($userRepo, $stationRepo))->handle(new GetUserQuery(UserId::generate()->value()));
})->throws(UserNotFoundException::class);