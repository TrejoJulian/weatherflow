<?php

declare(strict_types=1);

use App\Application\User\GetAllUsers\GetAllUsersHandler;
use App\Application\User\GetAllUsers\GetAllUsersQuery;
use App\Application\User\UserResponseWithSubscriptions;
use App\Domain\User\Entities\User;
use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserId;
use Tests\Unit\Domain\User\FakeUserRepository;
use Tests\Unit\Domain\WeatherStation\FakeWeatherStationRepository;

test('returns empty array when no users exist', function () {
    $userRepo    = new FakeUserRepository();
    $stationRepo = new FakeWeatherStationRepository();

    $result = (new GetAllUsersHandler($userRepo, $stationRepo))->handle(new GetAllUsersQuery());

    expect($result)->toBeEmpty();
});

test('returns all users as responses', function () {
    $userRepo    = new FakeUserRepository();
    $stationRepo = new FakeWeatherStationRepository();

    $userRepo->seed(
        User::create(UserId::generate(), new Email('john@example.com'), 'John', 'Doe'),
        User::create(UserId::generate(), new Email('jane@example.com'), 'Jane', 'Smith'),
    );

    $result = (new GetAllUsersHandler($userRepo, $stationRepo))->handle(new GetAllUsersQuery());

    expect($result)->toHaveCount(2)
        ->and($result[0])->toBeInstanceOf(UserResponseWithSubscriptions::class)
        ->and($result[1])->toBeInstanceOf(UserResponseWithSubscriptions::class);
});