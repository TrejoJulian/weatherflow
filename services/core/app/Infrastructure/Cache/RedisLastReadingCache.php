<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache;

use App\Application\Contracts\LastReadingCache;
use App\Domain\WeatherStation\ValueObjects\ClimateReading;
use App\Domain\WeatherStation\ValueObjects\StationId;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\Redis;

/**
 * Three copies of the same reading with different lifetimes:
 * - fresh (owm_fresh_ttl): current enough to skip the provider entirely.
 * - last (owm_cache_ttl): how long the reading is considered reliable.
 * - fallback (no TTL): last resort when the provider is down.
 */
final class RedisLastReadingCache implements LastReadingCache
{
    public function put(StationId $stationId, ClimateReading $reading): void
    {
        $payload = $this->serialize($reading);

        Redis::setex(
            $this->freshKeyFor($stationId),
            config('services.resilience.owm_fresh_ttl'),
            $payload,
        );

        Redis::setex(
            $this->lastKeyFor($stationId),
            config('services.resilience.owm_cache_ttl'),
            $payload,
        );

        Redis::set($this->fallbackKeyFor($stationId), $payload);
    }

    public function getFresh(StationId $stationId): ?ClimateReading
    {
        return $this->read($this->freshKeyFor($stationId));
    }

    public function get(StationId $stationId, bool $ignoreTtl = false): ?ClimateReading
    {
        return $this->read($ignoreTtl ? $this->fallbackKeyFor($stationId) : $this->lastKeyFor($stationId));
    }

    private function read(string $key): ?ClimateReading
    {
        $payload = Redis::get($key);

        // Missing keys come back as false (phpredis) or null (predis).
        if (! is_string($payload)) {
            return null;
        }

        return $this->deserialize($payload);
    }

    private function freshKeyFor(StationId $stationId): string
    {
        return "owm:fresh:{$stationId->value()}";
    }

    private function lastKeyFor(StationId $stationId): string
    {
        return "owm:last:{$stationId->value()}";
    }

    private function fallbackKeyFor(StationId $stationId): string
    {
        return "owm:fallback:{$stationId->value()}";
    }

    private function serialize(ClimateReading $reading): string
    {
        return json_encode([
            'temperature' => $reading->temperature,
            'humidity' => $reading->humidity,
            'atmospheric_pressure' => $reading->atmosphericPressure,
            'reported_at' => $reading->reportedAt
                ->setTimezone(new DateTimeZone('UTC'))
                ->format('Y-m-d\TH:i:s\Z'),
        ], JSON_THROW_ON_ERROR);
    }

    private function deserialize(string $payload): ClimateReading
    {
        $data = json_decode($payload, associative: true, flags: JSON_THROW_ON_ERROR);

        return new ClimateReading(
            temperature: (float) $data['temperature'],
            humidity: (float) $data['humidity'],
            atmosphericPressure: (float) $data['atmospheric_pressure'],
            reportedAt: new DateTimeImmutable($data['reported_at']),
        );
    }
}