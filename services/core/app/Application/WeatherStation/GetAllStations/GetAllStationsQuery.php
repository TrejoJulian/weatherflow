<?php

declare(strict_types=1);

namespace App\Application\WeatherStation\GetAllStations;

final class GetAllStationsQuery
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $createdFrom = null,
        public readonly ?string $createdTo = null,
    ) {}
}
