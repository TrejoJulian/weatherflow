<?php

declare(strict_types=1);

namespace App\Domain\User\Entities;

use App\Domain\User\Exceptions\UserAlreadySubscribedException;
use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\WeatherStation\ValueObjects\StationId;

final class User
{
    private function __construct(
        private readonly UserId $id,
        private Email $email,
        private string $firstName,
        private string $lastName,
        private array $subscriptions,
    ) {}

    public static function create(
        UserId $id,
        Email $email,
        string $firstName,
        string $lastName,
        array $subscriptions = [],
    ): self {
        return new self($id, $email, $firstName, $lastName, $subscriptions);
    }

    public function update(Email $email, string $firstName, string $lastName): void
    {
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }

    public function subscribe(StationId $stationId): void
    {
        if ($this->isSubscribedTo($stationId)) {
            throw new UserAlreadySubscribedException($stationId->value());
        }

        $this->subscriptions[] = $stationId;
    }

    public function unsubscribe(StationId $stationId): void
    {
        $this->subscriptions = array_values(
            array_filter(
                $this->subscriptions,
                fn (StationId $existing) => !$existing->equals($stationId),
            )
        );
    }

    public function isSubscribedTo(StationId $stationId): bool
    {
        return !empty(array_filter(
            $this->subscriptions,
            fn (StationId $existing) => $existing->equals($stationId),
        ));
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

    /** @return StationId[] */
    public function subscriptions(): array
    {
        return $this->subscriptions;
    }
}
