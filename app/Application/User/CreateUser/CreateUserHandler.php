<?php

declare(strict_types=1);

namespace App\Application\User\CreateUser;

use App\Application\User\UserResponse;
use App\Domain\User\Entities\User;
use App\Domain\User\Exceptions\DuplicateEmailException;
use App\Domain\User\Repositories\UserRepository;
use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserId;

final class CreateUserHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function handle(CreateUserCommand $command): UserResponse
    {
        $email = new Email($command->email);

        if ($this->userRepository->findByEmail($email) !== null) {
            throw new DuplicateEmailException($command->email);
        }

        $user = User::create(
            UserId::generate(),
            $email,
            $command->firstName,
            $command->lastName,
        );

        $this->userRepository->save($user);

        return UserResponse::fromEntity($user);
    }
}
