<?php

declare(strict_types=1);

namespace App\Application\User\GetUser;

use App\Application\User\UserResponse;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Domain\User\Repositories\UserRepository;
use App\Domain\User\ValueObjects\UserId;

final class GetUserHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function handle(GetUserQuery $query): UserResponse
    {
        $user = $this->userRepository->findById(UserId::fromString($query->id));

        if ($user === null) {
            throw new UserNotFoundException($query->id);
        }

        return UserResponse::fromEntity($user);
    }
}