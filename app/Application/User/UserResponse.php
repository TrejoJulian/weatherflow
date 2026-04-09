<?php

declare(strict_types=1);

namespace App\Application\User;

use App\Domain\User\Entities\User;

final class UserResponse
{
    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly string $firstName,
        public readonly string $lastName,
    ) {}

    public static function fromEntity(User $user): self
    {
        return new self(
            id:        $user->id()->value(),
            email:     $user->email()->value(),
            firstName: $user->firstName(),
            lastName:  $user->lastName(),
        );
    }
}