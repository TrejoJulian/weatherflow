<?php

declare(strict_types=1);

use App\Domain\WeatherStation\ValueObjects\Location;
use App\Infrastructure\Http\Clients\ClimateProviderFactory;
use App\Infrastructure\Http\Clients\OpenWeatherProvider;
use App\Domain\WeatherStation\Enums\ClimateProviderType;

const UNQ_LATITUDE = -34.9205;
const UNQ_LONGITUDE = -58.3838;

test('fetches real reading for UNQ coordinates', function () {
    $provider = app(OpenWeatherProvider::class);
    $location = new Location(UNQ_LATITUDE, UNQ_LONGITUDE);

    $reading = $provider->fetchCurrentReading($location);

    expect($reading->temperature)->toBeFloat()
        ->and($reading->humidity)->toBeBetween(0, 100)
        ->and($reading->atmosphericPressure)->toBeBetween(800, 1100)
        ->and($reading->reportedAt)->toBeInstanceOf(DateTimeImmutable::class);
})->skip(fn () => empty(env('OPENWEATHER_API_KEY')), 'OPENWEATHER_API_KEY not set');

test('reportedAt is recent', function () {
    $provider = app(OpenWeatherProvider::class);
    $location = new Location(UNQ_LATITUDE, UNQ_LONGITUDE);

    $reading = $provider->fetchCurrentReading($location);

    $now = new DateTimeImmutable();
    $twentyFourHoursAgo = $now->modify('-24 hours');

    expect($reading->reportedAt->getTimestamp())->toBeLessThanOrEqual($now->getTimestamp())
        ->and($reading->reportedAt->getTimestamp())->toBeGreaterThanOrEqual($twentyFourHoursAgo->getTimestamp());
})->skip(fn () => empty(env('OPENWEATHER_API_KEY')), 'OPENWEATHER_API_KEY not set');

test('factory resolves provider from container and fetches reading', function () {
    $factory = app(ClimateProviderFactory::class);
    $provider = $factory->for(ClimateProviderType::OpenWeather);
    $location = new Location(UNQ_LATITUDE, UNQ_LONGITUDE);

    $reading = $provider->fetchCurrentReading($location);

    expect($reading->temperature)->toBeFloat()
        ->and($reading->humidity)->toBeBetween(0, 100)
        ->and($reading->atmosphericPressure)->toBeBetween(800, 1100)
        ->and($reading->reportedAt)->toBeInstanceOf(DateTimeImmutable::class);
})->skip(fn () => empty(env('OPENWEATHER_API_KEY')), 'OPENWEATHER_API_KEY not set');

test('factory returns same OpenWeatherProvider instance', function () {
    $factory = app(ClimateProviderFactory::class);

    $first = $factory->for(ClimateProviderType::OpenWeather);
    $second = $factory->for(ClimateProviderType::OpenWeather);

    expect($first)->toBe($second)
        ->and($first)->toBeInstanceOf(OpenWeatherProvider::class);
})->skip(fn () => empty(env('OPENWEATHER_API_KEY')), 'OPENWEATHER_API_KEY not set');
