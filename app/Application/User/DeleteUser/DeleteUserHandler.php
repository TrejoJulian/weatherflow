<?php

declare(strict_types=1);

namespace App\Application\User\DeleteUser;

use App\Domain\User\Exceptions\UserNotFoundException;
use App\Domain\User\Repositories\UserRepository;
use App\Domain\User\ValueObjects\UserId;

final class DeleteUserHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function handle(DeleteUserCommand $command): void
    {
        $userId = UserId::fromString($command->id);

        if ($this->userRepository->findById($userId) === null) {
            throw new UserNotFoundException($command->id);
        }

        $this->userRepository->delete($userId);
    }
}