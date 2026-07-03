<?php

declare(strict_types=1);

namespace App\Domain\WeatherStation\Exceptions;

use RuntimeException;

final class NoCachedReadingAvailableException extends RuntimeException
{
    public function __construct(string $stationId)
    {
        parent::__construct(
            "No cached reading available for station '{$stationId}' and the climate provider is unavailable.",
        );
    }
}
