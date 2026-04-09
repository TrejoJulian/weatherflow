<?php

declare(strict_types=1);

namespace App\Application\User\GetAllUsers;

use App\Application\User\UserResponse;
use App\Domain\User\Repositories\UserRepository;

final class GetAllUsersHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    /** @return UserResponse[] */
    public function handle(GetAllUsersQuery $query): array
    {
        $users = $this->userRepository->findAll();

        return array_map(
            fn ($user) => UserResponse::fromEntity($user),
            $users,
        );
    }
}