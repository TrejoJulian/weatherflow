<?php

declare(strict_types=1);

namespace App\Application\User\DeleteUser;

use App\Domain\User\Exceptions\UserHasStationsException;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Domain\User\Repositories\UserRepository;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\WeatherStation\Repositories\WeatherStationRepository;

final class DeleteUserHandler
{
    public function __construct(
        private readonly UserRepository           $userRepository,
        private readonly WeatherStationRepository $stationRepository,
    ) {}

    public function handle(DeleteUserCommand $command): void
    {
        $userId = UserId::fromString($command->id);

        if ($this->userRepository->findById($userId) === null) {
            throw new UserNotFoundException($command->id);
        }

        if ($this->stationRepository->hasStationsOwnedBy($userId)) {
            throw new UserHasStationsException($command->id);
        }

        $this->userRepository->delete($userId);
    }
}
