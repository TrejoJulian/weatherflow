<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Clients;

use App\Domain\WeatherStation\Clients\ClimateProvider;
use App\Domain\WeatherStation\Enums\ClimateProviderType;

final class ClimateProviderFactory
{
    public function __construct(
        private readonly OpenWeatherProvider $openWeatherProvider,
    ) {}

    public function for(ClimateProviderType $type): ClimateProvider
    {
        return match ($type) {
            ClimateProviderType::OpenWeather => $this->openWeatherProvider,
        };
    }
}
