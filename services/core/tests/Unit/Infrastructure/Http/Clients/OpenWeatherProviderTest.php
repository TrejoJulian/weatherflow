<?php

declare(strict_types=1);

use App\Domain\WeatherStation\ValueObjects\ClimateReading;
use App\Domain\WeatherStation\ValueObjects\Location;
use App\Infrastructure\Http\Clients\OpenWeatherProvider;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    config([
        'services.openweather.key' => 'test-key',
        'services.openweather.base_url' => 'https://api.openweathermap.org/data/2.5',
        'services.resilience.owm_connect_timeout' => 1,
        'services.resilience.owm_timeout' => 2,
        'services.resilience.owm_retries' => 3,
    ]);
});

test('maps OpenWeather response to ClimateReading', function () {
    Http::fake([
        'https://api.openweathermap.org/data/2.5/weather*' => Http::response([
            'dt' => 1717862400,
            'main' => [
                'temp' => 21.4,
                'humidity' => 70,
                'pressure' => 1012,
            ],
        ], 200),
    ]);

    $location = new Location(-34.9205, -58.3838);
    $reading = (new OpenWeatherProvider)->fetchCurrentReading($location);

    expect($reading)->toBeInstanceOf(ClimateReading::class)
        ->and($reading->temperature)->toBe(21.4)
        ->and($reading->humidity)->toBe(70.0)
        ->and($reading->atmosphericPressure)->toBe(1012.0)
        ->and($reading->reportedAt)->toEqual(
            (new DateTimeImmutable)->setTimestamp(1717862400),
        );
});

test('sends correct GET request with query params', function () {
    Http::fake([
        'https://api.openweathermap.org/data/2.5/weather*' => Http::response([
            'dt' => 1717862400,
            'main' => ['temp' => 21.4, 'humidity' => 70, 'pressure' => 1012],
        ], 200),
    ]);

    $location = new Location(-34.9205, -58.3838);
    (new OpenWeatherProvider)->fetchCurrentReading($location);

    Http::assertSent(function ($request) {
        return str_starts_with($request->url(), 'https://api.openweathermap.org/data/2.5/weather')
            && $request->method() === 'GET'
            && $request['lat'] === -34.9205
            && $request['lon'] === -58.3838
            && $request['units'] === 'metric'
            && $request['appid'] === 'test-key';
    });
});

test('uses API key from config', function () {
    config(['services.openweather.key' => 'custom-api-key']);

    Http::fake([
        'https://api.openweathermap.org/data/2.5/weather*' => Http::response([
            'dt' => 1717862400,
            'main' => ['temp' => 21.4, 'humidity' => 70, 'pressure' => 1012],
        ], 200),
    ]);

    $location = new Location(-34.9205, -58.3838);
    (new OpenWeatherProvider)->fetchCurrentReading($location);

    Http::assertSent(fn ($request) => $request['appid'] === 'custom-api-key');
});

test('throws RuntimeException when API key is missing', function () {
    config(['services.openweather.key' => null]);

    $location = new Location(-34.9205, -58.3838);

    expect(fn () => (new OpenWeatherProvider)->fetchCurrentReading($location))
        ->toThrow(RuntimeException::class, 'OPENWEATHER_API_KEY is not configured.');
});

test('throws RuntimeException when API key is empty', function () {
    config(['services.openweather.key' => '']);

    $location = new Location(-34.9205, -58.3838);

    expect(fn () => (new OpenWeatherProvider)->fetchCurrentReading($location))
        ->toThrow(RuntimeException::class, 'OPENWEATHER_API_KEY is not configured.');
});

test('propagates HTTP error on 401', function () {
    Http::fake([
        'https://api.openweathermap.org/data/2.5/weather*' => Http::response(['message' => 'Unauthorized'], 401),
    ]);

    $location = new Location(-34.9205, -58.3838);

    expect(fn () => (new OpenWeatherProvider)->fetchCurrentReading($location))
        ->toThrow(RequestException::class);
});

test('propagates HTTP error on 500', function () {
    Http::fake([
        'https://api.openweathermap.org/data/2.5/weather*' => Http::response(['message' => 'Server error'], 500),
    ]);

    $location = new Location(-34.9205, -58.3838);

    expect(fn () => (new OpenWeatherProvider)->fetchCurrentReading($location))
        ->toThrow(RequestException::class);
});

test('retries a 503 and succeeds once OWM recovers', function () {
    Http::fake([
        'https://api.openweathermap.org/data/2.5/weather*' => Http::sequence()
            ->push(['message' => 'Service Unavailable'], 503)
            ->push(['message' => 'Service Unavailable'], 503)
            ->push([
                'dt' => 1717862400,
                'main' => ['temp' => 21.4, 'humidity' => 70, 'pressure' => 1012],
            ], 200),
    ]);

    $location = new Location(-34.9205, -58.3838);
    $reading = (new OpenWeatherProvider)->fetchCurrentReading($location);

    expect($reading)->toBeInstanceOf(ClimateReading::class)
        ->and($reading->temperature)->toBe(21.4);
    Http::assertSentCount(3);
});

test('exhausts the configured retries on a persistent 503 and then throws', function () {
    Http::fake([
        'https://api.openweathermap.org/data/2.5/weather*' => Http::response(['message' => 'Service Unavailable'], 503),
    ]);

    $location = new Location(-34.9205, -58.3838);

    expect(fn () => (new OpenWeatherProvider)->fetchCurrentReading($location))
        ->toThrow(RequestException::class);
    Http::assertSentCount(3);
});

test('does not retry a 401 and logs a clear configuration error', function () {
    Log::spy();

    Http::fake([
        'https://api.openweathermap.org/data/2.5/weather*' => Http::response(['message' => 'Invalid API key'], 401),
    ]);

    $location = new Location(-34.9205, -58.3838);

    expect(fn () => (new OpenWeatherProvider)->fetchCurrentReading($location))
        ->toThrow(RequestException::class);
    Http::assertSentCount(1);
    Log::shouldHaveReceived('error')
        ->once()
        ->withArgs(fn (string $message) => str_contains($message, '401'));
});

test('does not retry a 404 for an uncovered location', function () {
    Http::fake([
        'https://api.openweathermap.org/data/2.5/weather*' => Http::response(['message' => 'Not found'], 404),
    ]);

    $location = new Location(-34.9205, -58.3838);

    expect(fn () => (new OpenWeatherProvider)->fetchCurrentReading($location))
        ->toThrow(RequestException::class);
    Http::assertSentCount(1);
});

test('the number of retries is driven by OWM_RETRIES config', function () {
    config(['services.resilience.owm_retries' => 5]);

    Http::fake([
        'https://api.openweathermap.org/data/2.5/weather*' => Http::response(['message' => 'Service Unavailable'], 503),
    ]);

    $location = new Location(-34.9205, -58.3838);

    expect(fn () => (new OpenWeatherProvider)->fetchCurrentReading($location))
        ->toThrow(RequestException::class);
    Http::assertSentCount(5);
});
