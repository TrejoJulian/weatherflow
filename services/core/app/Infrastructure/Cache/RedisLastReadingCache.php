<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache;

use App\Application\Contracts\LastReadingCache;
use App\Domain\WeatherStation\ValueObjects\ClimateReading;
use App\Domain\WeatherStation\ValueObjects\StationId;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\Redis;

final class RedisLastReadingCache implements LastReadingCache
{
    public function put(StationId $stationId, ClimateReading $reading): void
    {
        Redis::setex(
            $this->keyFor($stationId),
            config('services.resilience.owm_cache_ttl'),
            $this->serialize($reading),
        );
    }

    public function get(StationId $stationId): ?ClimateReading
    {
        $payload = Redis::get($this->keyFor($stationId));

        // Missing keys come back as false (phpredis) or null (predis).
        if (! is_string($payload)) {
            return null;
        }

        return $this->deserialize($payload);
    }

    private function keyFor(StationId $stationId): string
    {
        return "owm:last:{$stationId->value()}";
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
