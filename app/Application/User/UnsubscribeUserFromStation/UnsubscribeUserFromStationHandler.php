<?php

declare(strict_types=1);

namespace App\Application\User\UnsubscribeUserFromStation;

use App\Domain\User\Exceptions\UserNotFoundException;
use App\Domain\User\Repositories\UserRepository;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\WeatherStation\ValueObjects\StationId;

final class UnsubscribeUserFromStationHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function handle(UnsubscribeUserFromStationCommand $command): void
    {
        $userId    = UserId::fromString($command->userId);
        $stationId = StationId::fromString($command->stationId);

        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            throw new UserNotFoundException($command->userId);
        }

        $user->unsubscribe($stationId);
        $this->userRepository->save($user);
    }
}