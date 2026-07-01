<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Cache;

use App\Application\Contracts\LastReadingCache;
use App\Domain\WeatherStation\ValueObjects\ClimateReading;
use App\Domain\WeatherStation\ValueObjects\StationId;
use RuntimeException;

/**
 * Simulates Redis being unavailable: every write blows up. Used to prove the
 * ingestion keeps going (logs and publishes) when the cache cannot be written.
 */
final class FaultyLastReadingCache implements LastReadingCache
{
    public function put(StationId $stationId, ClimateReading $reading): void
    {
        throw new RuntimeException('Redis is unavailable');
    }

    public function get(StationId $stationId): ?ClimateReading
    {
        return null;
    }
}
