<?php

declare(strict_types=1);

namespace App\Domain\User\Entities;

use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserId;

final class User
{
    private function __construct(
        private readonly UserId $id,
        private Email $email,
        private string $firstName,
        private string $lastName,
    ) {}

    public static function create(
        UserId $id,
        Email $email,
        string $firstName,
        string $lastName,
    ): self {
        return new self($id, $email, $firstName, $lastName);
    }

    public function update(Email $email, string $firstName, string $lastName): void
    {
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }

    public function id(): UserId
    {
        return $this->id;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function firstName(): string
    {
        return $this->firstName;
    }

    public function lastName(): string
    {
        return $this->lastName;
    }
}
