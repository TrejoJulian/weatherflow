<?php

declare(strict_types=1);

namespace App\Domain\WeatherStation\Exceptions;

use RuntimeException;

final class StationHasMeasurementsException extends RuntimeException
{
    public function __construct(string $stationId)
    {
        parent::__construct("Station {$stationId} has measurements and cannot be deleted.");
    }
}
