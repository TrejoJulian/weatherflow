<?php

declare(strict_types=1);

namespace App\Application\User;

use App\Domain\User\Entities\User;
use App\Domain\WeatherStation\Entities\WeatherStation;

final class UserResponseWithSubscriptions
{
    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly array  $subscriptions,
    ) {}

    /**
     * @param array<string, WeatherStation> $subscribedStationsById
     */
    public static function fromEntity(User $user, array $subscribedStationsById): self
    {
        $subscriptions = [];
        foreach ($user->subscriptions() as $stationId) {
            $station = $subscribedStationsById[$stationId->value()] ?? null;
            if ($station !== null) {
                $subscriptions[] = SubscriptionResponse::fromStation($station);
            }
        }

        return new self(
            id:            $user->id()->value(),
            email:         $user->email()->value(),
            firstName:     $user->firstName(),
            lastName:      $user->lastName(),
            subscriptions: $subscriptions,
        );
    }
}
