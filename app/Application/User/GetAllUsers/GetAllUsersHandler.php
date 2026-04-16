<?php

declare(strict_types=1);

namespace App\Application\User\GetAllUsers;

use App\Application\User\AbstractUserHandler;
use App\Application\User\UserResponseWithSubscriptions;
use App\Domain\User\Entities\User;

final class GetAllUsersHandler extends AbstractUserHandler
{
    /** @return UserResponseWithSubscriptions[] */
    public function handle(GetAllUsersQuery $query): array
    {
        return array_map(
            fn (User $user) => UserResponseWithSubscriptions::fromEntity($user, $this->resolveSubscribedStationsById($user)),
            $this->userRepository->findAll(),
        );
    }
}