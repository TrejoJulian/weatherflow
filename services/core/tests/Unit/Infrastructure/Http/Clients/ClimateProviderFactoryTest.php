<?php

declare(strict_types=1);

use App\Domain\WeatherStation\Clients\ClimateProvider;
use App\Domain\WeatherStation\Enums\ClimateProviderType;
use App\Infrastructure\Http\Clients\ClimateProviderFactory;
use App\Infrastructure\Http\Clients\OpenWeatherProvider;
use Tests\Unit\Infrastructure\Resilience\GaneshaTestDoubles;

test('for OpenWeather returns OpenWeatherProvider instance', function () {
    $openWeatherProvider = new OpenWeatherProvider(GaneshaTestDoubles::alwaysAvailable());
    $factory = new ClimateProviderFactory($openWeatherProvider);

    $provider = $factory->for(ClimateProviderType::OpenWeather);

    expect($provider)->toBeInstanceOf(OpenWeatherProvider::class)
        ->and($provider)->toBeInstanceOf(ClimateProvider::class)
        ->and($provider)->toBe($openWeatherProvider);
});
