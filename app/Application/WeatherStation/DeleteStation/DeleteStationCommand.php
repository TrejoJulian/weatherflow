<?php

declare(strict_types=1);

namespace App\Application\WeatherStation\DeleteStation;

final class DeleteStationCommand
{
    public function __construct(
        public readonly string $id,
    ) {}
}