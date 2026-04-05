<?php

declare(strict_types=1);

namespace App\Application\User;

final class UserResponse
{
    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly string $firstName,
        public readonly string $lastName,
    ) {}
}