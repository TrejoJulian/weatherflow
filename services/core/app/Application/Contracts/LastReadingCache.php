<?php

declare(strict_types=1);

namespace App\Application\Contracts;

use App\Domain\WeatherStation\ValueObjects\ClimateReading;
use App\Domain\WeatherStation\ValueObjects\StationId;

interface LastReadingCache
{
    public function put(StationId $stationId, ClimateReading $reading): void;

    /**
     * The reading only while it is fresh enough to skip the provider entirely.
     */
    public function getFresh(StationId $stationId): ?ClimateReading;

    public function get(StationId $stationId, bool $ignoreTtl = false): ?ClimateReading;
}