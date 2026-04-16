<?php

declare(strict_types=1);

namespace App\Application\User\SubscribeUserToStation;

use App\Application\User\AbstractUserHandler;
use App\Application\User\UserResponseWithSubscriptions;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\WeatherStation\Exceptions\StationNotFoundException;
use App\Domain\WeatherStation\ValueObjects\StationId;

final class SubscribeUserToStationHandler extends AbstractUserHandler
{
    public function handle(SubscribeUserToStationCommand $command): UserResponseWithSubscriptions
    {
        $userId    = UserId::fromString($command->userId);
        $stationId = StationId::fromString($command->stationId);

        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            throw new UserNotFoundException($command->userId);
        }

        if ($this->stationRepository->findById($stationId) === null) {
            throw new StationNotFoundException($command->stationId);
        }

        $user->subscribe($stationId);
        $this->userRepository->save($user);

        return UserResponseWithSubscriptions::fromEntity($user, $this->resolveSubscribedStationsById($user));
    }
}