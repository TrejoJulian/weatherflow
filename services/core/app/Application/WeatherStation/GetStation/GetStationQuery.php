<?php

declare(strict_types=1);

namespace App\Application\WeatherStation\GetStation;

final class GetStationQuery
{
    public function __construct(
        public readonly string $id,
    ) {}
}