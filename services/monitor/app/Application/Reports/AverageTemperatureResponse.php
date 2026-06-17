<?php

declare(strict_types=1);

namespace App\Application\Reports;

final class AverageTemperatureResponse
{
    public function __construct(
        public readonly string  $stationId,
        public readonly string  $window,
        public readonly ?float  $averageTemperature,
        public readonly string  $from,
        public readonly string  $to,
        public readonly ?string $message = null,
    ) {}
}
