<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Clients;

use App\Domain\WeatherStation\Clients\ClimateProvider;
use App\Domain\WeatherStation\ValueObjects\ClimateReading;
use App\Domain\WeatherStation\ValueObjects\Location;
use DateTimeImmutable;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class OpenWeatherProvider implements ClimateProvider
{
    private const TIMEOUT_SECONDS = 10;

    public function fetchCurrentReading(Location $location): ClimateReading
    {
        $apiKey = config('services.openweather.key');
        if (empty($apiKey)) {
            throw new RuntimeException('OPENWEATHER_API_KEY is not configured.');
        }

        $response = Http::baseUrl(config('services.openweather.base_url'))
            ->timeout(self::TIMEOUT_SECONDS)
            ->get('/weather', [
                'lat'   => $location->latitude(),
                'lon'   => $location->longitude(),
                'units' => 'metric',
                'appid' => $apiKey,
            ]);

        $response->throw();

        $data = $response->json();

        return new ClimateReading(
            temperature: (float) $data['main']['temp'],
            humidity: (float) $data['main']['humidity'],
            atmosphericPressure: (float) $data['main']['pressure'],
            reportedAt: (new DateTimeImmutable())->setTimestamp((int) $data['dt']),
        );
    }
}
