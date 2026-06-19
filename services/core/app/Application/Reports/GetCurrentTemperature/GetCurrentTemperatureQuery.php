<?php

declare(strict_types=1);

namespace App\Application\Reports\GetCurrentTemperature;

final class GetCurrentTemperatureQuery
{
    public function __construct(
        public readonly string $stationId,
    ) {}
}
