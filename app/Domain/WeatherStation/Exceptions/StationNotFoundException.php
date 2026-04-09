<?php

declare(strict_types=1);

namespace App\Domain\WeatherStation\Exceptions;

use RuntimeException;

final class StationNotFoundException extends RuntimeException
{
    public function __construct(string $stationId)
    {
        parent::__construct("Weather station not found: '{$stationId}'");
    }
}
