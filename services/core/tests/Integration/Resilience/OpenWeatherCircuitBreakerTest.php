<?php

declare(strict_types=1);

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Builder;
use Ackintosh\Ganesha\Storage\Adapter\Redis as GaneshaRedisAdapter;
use App\Domain\WeatherStation\Exceptions\ClimateProviderUnavailableException;
use App\Domain\WeatherStation\ValueObjects\Location;
use App\Infrastructure\Http\Clients\OpenWeatherProvider;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Tests\Unit\Infrastructure\Metrics\FakeMetricsRecorder;

beforeEach(function () {
    try {
        Redis::connection()->ping();
        Redis::connection()->flushdb();
    } catch (Throwable $exception) {
        $this->markTestSkipped('Redis is not reachable: '.$exception->getMessage());
    }

    Http::fake([
        'https://api.openweathermap.org/data/2.5/weather*' => Http::response(['message' => 'Service Unavailable'], 503),
    ]);

    config([
        'services.openweather.key' => 'test-key',
        'services.openweather.base_url' => 'https://api.openweathermap.org/data/2.5',
        'services.resilience.owm_connect_timeout' => 1,
        'services.resilience.owm_timeout' => 2,
        'services.resilience.owm_retries' => 1,
        'services.resilience.breaker_threshold' => 3,
        'services.resilience.breaker_reset' => 300,
    ]);
});

afterEach(function () {
    try {
        Redis::connection()->flushdb();
    } catch (Throwable) {
        // Redis was unavailable; beforeEach already skipped or connection dropped.
    }
});

function ganeshaForIntegrationTest(): Ganesha
{
    $breakerThreshold = config('services.resilience.breaker_threshold');
    $breakerReset = config('services.resilience.breaker_reset');

    return Builder::withRateStrategy()
        ->adapter(new GaneshaRedisAdapter(Redis::connection()->client()))
        ->failureRateThreshold(100)
        ->minimumRequests($breakerThreshold)
        ->intervalToHalfOpen($breakerReset)
        ->timeWindow(max($breakerReset * 2, 60))
        ->build();
}

test('opens the circuit after repeated transient failures and rejects further calls without HTTP', function () {
    $location = new Location(-34.9205, -58.3838);
    $ganesha = ganeshaForIntegrationTest();
    $provider = new OpenWeatherProvider($ganesha, new FakeMetricsRecorder);

    for ($attempt = 0; $attempt < 3; $attempt++) {
        expect(fn () => $provider->fetchCurrentReading($location))
            ->toThrow(RequestException::class);
    }

    expect(fn () => $provider->fetchCurrentReading($location))
        ->toThrow(ClimateProviderUnavailableException::class);

    Http::assertSentCount(3);
});

test('shares circuit breaker state in Redis across provider instances', function () {
    $location = new Location(-34.9205, -58.3838);
    $ganesha = ganeshaForIntegrationTest();
    $schedulerProvider = new OpenWeatherProvider($ganesha, new FakeMetricsRecorder);
    $endpointProvider = new OpenWeatherProvider($ganesha, new FakeMetricsRecorder);

    for ($attempt = 0; $attempt < 3; $attempt++) {
        expect(fn () => $schedulerProvider->fetchCurrentReading($location))
            ->toThrow(RequestException::class);
    }

    expect(fn () => $endpointProvider->fetchCurrentReading($location))
        ->toThrow(ClimateProviderUnavailableException::class);

    Http::assertSentCount(3);
});
