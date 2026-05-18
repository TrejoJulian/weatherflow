<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\User;

use App\Domain\User\Entities\User;
use App\Domain\User\Repositories\UserRepository;
use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserId;

final class FakeUserRepository implements UserRepository
{
    /** @var User[] */
    private array $users = [];

    public function save(User $user): void
    {
        $this->users[$user->id()->value()] = $user;
    }

    public function findById(UserId $id): ?User
    {
        return $this->users[$id->value()] ?? null;
    }

    public function findByEmail(Email $email): ?User
    {
        foreach ($this->users as $user) {
            if ($user->email()->equals($email)) {
                return $user;
            }
        }

        return null;
    }

    public function findAll(): array
    {
        return array_values($this->users);
    }

    public function delete(UserId $id): void
    {
        unset($this->users[$id->value()]);
    }

    public function seed(User ...$users): void
    {
        foreach ($users as $user) {
            $this->users[$user->id()->value()] = $user;
        }
    }
}
