<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Cache;

use App\Application\Contracts\LastReadingCache;
use App\Domain\WeatherStation\ValueObjects\ClimateReading;
use App\Domain\WeatherStation\ValueObjects\StationId;

final class FakeLastReadingCache implements LastReadingCache
{
    /** @var array<string, ClimateReading> */
    private array $freshReadings = [];

    /** @var array<string, ClimateReading> */
    private array $readings = [];

    /** @var array<string, ClimateReading> */
    private array $fallbackReadings = [];

    private int $putCount = 0;

    public function put(StationId $stationId, ClimateReading $reading): void
    {
        $this->freshReadings[$stationId->value()] = $reading;
        $this->readings[$stationId->value()] = $reading;
        $this->fallbackReadings[$stationId->value()] = $reading;
        $this->putCount++;
    }

    public function getFresh(StationId $stationId): ?ClimateReading
    {
        return $this->freshReadings[$stationId->value()] ?? null;
    }

    public function get(StationId $stationId, bool $ignoreTtl = false): ?ClimateReading
    {
        if ($ignoreTtl) {
            return $this->fallbackReadings[$stationId->value()] ?? null;
        }

        return $this->readings[$stationId->value()] ?? null;
    }

    /**
     * Simulates the fresh TTL expiring: the reading stops being fresh but is
     * still servable through the other keys, exactly like in Redis.
     */
    public function expireFreshEntries(): void
    {
        $this->freshReadings = [];
    }

    public function wasPut(StationId $stationId): bool
    {
        return isset($this->readings[$stationId->value()]);
    }

    public function getPutCount(): int
    {
        return $this->putCount;
    }
}
