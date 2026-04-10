<?php

declare(strict_types=1);

use App\Application\WeatherStation\CreateStation\CreateStationCommand;
use App\Application\WeatherStation\CreateStation\CreateStationHandler;
use App\Application\WeatherStation\StationResponse;
use App\Domain\User\Entities\User;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\WeatherStation\Enums\StationStatus;
use Tests\Unit\Domain\User\FakeUserRepository;
use Tests\Unit\Domain\WeatherStation\FakeWeatherStationRepository;

function makeUser(): User
{
    return User::create(UserId::generate(), new Email('owner@example.com'), 'Owner', 'User');
}

function makeCreateStationCommand(string $ownerId, ?string $status = null): CreateStationCommand
{
    return new CreateStationCommand(
        ownerId:     $ownerId,
        stationName: 'Estación Central',
        latitude:    -34.6037,
        longitude:   -58.3816,
        sensorModel: 'Davis Vantage Pro2',
        status:      $status,
    );
}

test('creates a station and returns a response', function () {
    $user = makeUser();
    $userRepo = new FakeUserRepository();
    $userRepo->seed($user);

    $response = (new CreateStationHandler(new FakeWeatherStationRepository(), $userRepo))
        ->handle(makeCreateStationCommand($user->id()->value()));

    expect($response)->toBeInstanceOf(StationResponse::class)
        ->and($response->ownerId)->toBe($user->id()->value())
        ->and($response->stationName)->toBe('Estación Central')
        ->and($response->status)->toBe('active');
});

test('defaults status to active', function () {
    $user = makeUser();
    $userRepo = new FakeUserRepository();
    $userRepo->seed($user);

    $response = (new CreateStationHandler(new FakeWeatherStationRepository(), $userRepo))
        ->handle(makeCreateStationCommand($user->id()->value()));

    expect($response->status)->toBe(StationStatus::Active->value);
});

test('throws when owner does not exist', function () {
    (new CreateStationHandler(new FakeWeatherStationRepository(), new FakeUserRepository()))
        ->handle(makeCreateStationCommand('00000000-0000-4000-a000-000000000000'));
})->throws(UserNotFoundException::class);