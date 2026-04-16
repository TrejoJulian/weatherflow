<?php

declare(strict_types=1);

namespace App\Application\User\UpdateUser;

use App\Application\User\UserResponse;
use App\Domain\User\Exceptions\DuplicateEmailException;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Domain\User\Repositories\UserRepository;
use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserId;

final class UpdateUserHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function handle(UpdateUserCommand $command): UserResponse
    {
        $userId = UserId::fromString($command->id);
        $user   = $this->userRepository->findById($userId);

        if ($user === null) {
            throw new UserNotFoundException($command->id);
        }

        $newEmail = new Email($command->email);

        $existingWithEmail = $this->userRepository->findByEmail($newEmail);
        if ($existingWithEmail !== null && !$existingWithEmail->id()->equals($userId)) {
            throw new DuplicateEmailException($command->email);
        }

        $user->update($newEmail, $command->firstName, $command->lastName);
        $this->userRepository->save($user);

        return UserResponse::fromEntity($user);
    }
}