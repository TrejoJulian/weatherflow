<?php

declare(strict_types=1);

use App\Application\Contracts\LastReadingCache;
use App\Domain\WeatherStation\ValueObjects\ClimateReading;
use App\Domain\WeatherStation\ValueObjects\StationId;
use Illuminate\Support\Facades\Redis;

// These tests hit a real Redis. phpunit.xml pins the connection to logical DB 15 so
// they never touch the app's data on DB 0; afterEach flushes that test-only database.

beforeEach(function () {
    try {
        Redis::connection()->ping();
    } catch (Throwable $exception) {
        $this->markTestSkipped('Redis is not reachable: '.$exception->getMessage());
    }
});

afterEach(function () {
    Redis::connection()->flushdb();
});

test('stores and retrieves a reading round-trip', function () {
    $cache = app(LastReadingCache::class);
    $stationId = StationId::generate();
    $reading = new ClimateReading(21.4, 70.0, 1012.0, new DateTimeImmutable('2026-06-29T14:50:00Z'));

    $cache->put($stationId, $reading);
    $restored = $cache->get($stationId);

    expect($restored)->toBeInstanceOf(ClimateReading::class)
        ->and($restored->temperature)->toBe(21.4)
        ->and($restored->humidity)->toBe(70.0)
        ->and($restored->atmosphericPressure)->toBe(1012.0)
        ->and($restored->reportedAt->format('Y-m-d\TH:i:s\Z'))->toBe('2026-06-29T14:50:00Z');
});

test('returns null when nothing was cached for the station', function () {
    expect(app(LastReadingCache::class)->get(StationId::generate()))->toBeNull();
});

test('sets a TTL taken from config on the cached reading', function () {
    config(['services.resilience.owm_cache_ttl' => 600]);
    $stationId = StationId::generate();

    app(LastReadingCache::class)->put($stationId, new ClimateReading(20.0, 50.0, 1000.0, new DateTimeImmutable));

    $ttl = Redis::connection()->ttl("owm:last:{$stationId->value()}");

    expect($ttl)->toBeGreaterThan(0)
        ->and($ttl)->toBeLessThanOrEqual(600);
});

test('serves the fallback key ignoring TTL after the fresh key expires', function () {
    $cache = app(LastReadingCache::class);
    $stationId = StationId::generate();
    $reading = new ClimateReading(18.5, 65.0, 1005.0, new DateTimeImmutable('2026-06-29T14:50:00Z'));

    $cache->put($stationId, $reading);

    Redis::connection()->del("owm:last:{$stationId->value()}");

    expect($cache->get($stationId))->toBeNull();

    $fallback = $cache->get($stationId, ignoreTtl: true);

    expect($fallback)->toBeInstanceOf(ClimateReading::class)
        ->and($fallback->temperature)->toBe(18.5);
});

test('overwrites the previous reading for the same station', function () {
    $cache = app(LastReadingCache::class);
    $stationId = StationId::generate();

    $cache->put($stationId, new ClimateReading(10.0, 40.0, 990.0, new DateTimeImmutable));
    $cache->put($stationId, new ClimateReading(25.5, 60.0, 1015.0, new DateTimeImmutable));

    expect($cache->get($stationId)->temperature)->toBe(25.5);
});
