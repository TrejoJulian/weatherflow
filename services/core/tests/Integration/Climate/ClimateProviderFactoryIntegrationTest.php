<?php

declare(strict_types=1);

use App\Domain\WeatherStation\Clients\ClimateProvider;
use App\Domain\WeatherStation\Enums\ClimateProviderType;
use App\Infrastructure\Http\Clients\ClimateProviderFactory;

test('resolves from container as singleton', function () {
    $first = app(ClimateProviderFactory::class);
    $second = app(ClimateProviderFactory::class);

    expect($first)->toBe($second);
})->skip(fn () => empty(env('OPENWEATHER_API_KEY')), 'OPENWEATHER_API_KEY not set');

test('for OpenWeather returns ClimateProvider implementation', function () {
    $factory = app(ClimateProviderFactory::class);

    $provider = $factory->for(ClimateProviderType::OpenWeather);

    expect($provider)->toBeInstanceOf(ClimateProvider::class);
})->skip(fn () => empty(env('OPENWEATHER_API_KEY')), 'OPENWEATHER_API_KEY not set');
