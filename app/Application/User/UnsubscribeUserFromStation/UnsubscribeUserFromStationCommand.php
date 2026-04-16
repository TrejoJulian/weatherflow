<?php

declare(strict_types=1);

namespace App\Application\User\UnsubscribeUserFromStation;

final class UnsubscribeUserFromStationCommand
{
    public function __construct(
        public readonly string $userId,
        public readonly string $stationId,
    ) {}
}