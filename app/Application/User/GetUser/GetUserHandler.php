<?php

declare(strict_types=1);

namespace App\Application\User\GetUser;

use App\Application\User\AbstractUserHandler;
use App\Application\User\UserResponseWithSubscriptions;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Domain\User\ValueObjects\UserId;

final class GetUserHandler extends AbstractUserHandler
{
    public function handle(GetUserQuery $query): UserResponseWithSubscriptions
    {
        $user = $this->userRepository->findById(UserId::fromString($query->id));

        if ($user === null) {
            throw new UserNotFoundException($query->id);
        }

        return UserResponseWithSubscriptions::fromEntity($user, $this->resolveSubscribedStationsById($user));
    }
}