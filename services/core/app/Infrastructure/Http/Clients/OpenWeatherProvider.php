<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Clients;

use Ackintosh\Ganesha;
use App\Domain\WeatherStation\Clients\ClimateProvider;
use App\Domain\WeatherStation\Exceptions\ClimateProviderUnavailableException;
use App\Domain\WeatherStation\ValueObjects\ClimateReading;
use App\Domain\WeatherStation\ValueObjects\Location;
use DateTimeImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

final class OpenWeatherProvider implements ClimateProvider
{
    private const SERVICE = 'openweather';

    public function __construct(
        private readonly Ganesha $ganesha,
    ) {}

    public function fetchCurrentReading(Location $location): ClimateReading
    {
        if (! $this->ganesha->isAvailable(self::SERVICE)) {
            throw new ClimateProviderUnavailableException;
        }

        try {
            $reading = $this->fetchFromApi($location);
            $this->ganesha->success(self::SERVICE);

            return $reading;
        } catch (Throwable $exception) {
            if ($this->isRetryable($exception)) {
                $this->ganesha->failure(self::SERVICE);
            }

            throw $exception;
        }
    }

    private function fetchFromApi(Location $location): ClimateReading
    {
        $apiKey = config('services.openweather.key');
        if (empty($apiKey)) {
            throw new RuntimeException('OPENWEATHER_API_KEY is not configured.');
        }

        $response = Http::baseUrl(config('services.openweather.base_url'))
            ->connectTimeout(config('services.resilience.owm_connect_timeout'))
            ->timeout(config('services.resilience.owm_timeout'))
            ->retry(
                times: config('services.resilience.owm_retries'),
                sleepMilliseconds: fn (int $attempt) => $attempt * 200, // 200ms, 400ms, 600ms...
                when: fn (Throwable $exception) => $this->isRetryable($exception),
                throw: false,
            )
            ->get('/weather', [
                'lat' => $location->latitude(),
                'lon' => $location->longitude(),
                'units' => 'metric',
                'appid' => $apiKey,
            ]);

        $this->logConfigurationErrors($response);

        $response->throw();

        $data = $response->json();

        return new ClimateReading(
            temperature: (float) $data['main']['temp'],
            humidity: (float) $data['main']['humidity'],
            atmosphericPressure: (float) $data['main']['pressure'],
            reportedAt: (new DateTimeImmutable)->setTimestamp((int) $data['dt']),
        );
    }

    /**
     * A failed OWM call is worth retrying when it is a transient failure: a network
     * hiccup/timeout (ConnectionException) or an OWM-side status (5xx or 429 rate-limit).
     * Configuration or bad-data errors (401/403/404) are not retryable.
     */
    private function isRetryable(Throwable $exception): bool
    {
        return $exception instanceof ConnectionException
            || ($exception instanceof RequestException && $this->isTransientStatus($exception->response->status()));
    }

    private function isTransientStatus(int $status): bool
    {
        return $status === 429 || $status >= 500;
    }

    private function logConfigurationErrors(Response $response): void
    {
        if (in_array($response->status(), [401, 403], true)) {
            Log::error("OpenWeatherMap rejected the request with HTTP {$response->status()}: the API key is invalid or unauthorized. This is a configuration error and will not be retried.");
        }
    }
}
