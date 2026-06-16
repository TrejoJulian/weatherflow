<?php

declare(strict_types=1);

namespace App\Domain\WeatherStation\Enums;

enum ClimateProviderType: string
{
    case OpenWeather = 'openweather';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $type) => $type->value, self::cases());
    }
}
