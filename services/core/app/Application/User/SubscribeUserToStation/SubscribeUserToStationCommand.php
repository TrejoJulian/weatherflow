<?php

declare(strict_types=1);

namespace App\Application\User\SubscribeUserToStation;

final class SubscribeUserToStationCommand
{
    public function __construct(
        public readonly string $userId,
        public readonly string $stationId,
    ) {}
}