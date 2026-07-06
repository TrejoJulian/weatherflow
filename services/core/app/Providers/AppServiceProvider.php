<?php

declare(strict_types=1);

namespace App\Providers;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Builder;
use Ackintosh\Ganesha\Storage\Adapter\Redis as GaneshaRedisAdapter;
use App\Application\Contracts\EventPublisher;
use App\Application\Contracts\LastReadingCache;
use App\Application\Contracts\MetricsRecorder;
use App\Application\IngestMeasurements\IngestMeasurementsHandler;
use App\Application\WeatherStation\UpdateStation\UpdateStationHandler;
use App\Domain\User\Repositories\UserRepository;
use App\Domain\WeatherStation\Repositories\WeatherStationRepository;
use App\Infrastructure\Cache\RedisLastReadingCache;
use App\Infrastructure\Http\Clients\ClimateProviderFactory;
use App\Infrastructure\Http\Clients\OpenWeatherProvider;
use App\Infrastructure\Messaging\RabbitMQEventPublisher;
use App\Infrastructure\Metrics\PrometheusMetricsRecorder;
use App\Infrastructure\Persistence\MongoDB\MongoUserRepository;
use App\Infrastructure\Persistence\MongoDB\MongoWeatherStationRepository;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\ServiceProvider;
use OpenTelemetry\API\Trace\TracerInterface;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepository::class, MongoUserRepository::class);
        $this->app->bind(WeatherStationRepository::class, MongoWeatherStationRepository::class);
        $this->app->bind(EventPublisher::class, RabbitMQEventPublisher::class);
        $this->app->singleton(LastReadingCache::class, RedisLastReadingCache::class);
        $this->app->singleton(MetricsRecorder::class, PrometheusMetricsRecorder::class);

        $this->app->bind(UpdateStationHandler::class, function ($app) {
            return new UpdateStationHandler(
                $app->make(WeatherStationRepository::class),
                $app->make(UserRepository::class),
                $app->make(EventPublisher::class),
                config('services.queues.stations'),
            );
        });

        $this->app->singleton(Ganesha::class, function ($app) {
            $breakerThreshold = config('services.resilience.breaker_threshold');
            $breakerReset = config('services.resilience.breaker_reset');

            $ganesha = Builder::withRateStrategy()
                ->adapter(new GaneshaRedisAdapter(Redis::connection()->client()))
                ->failureRateThreshold(100)
                ->minimumRequests($breakerThreshold)
                ->intervalToHalfOpen($breakerReset)
                ->timeWindow(max($breakerReset * 2, 60))
                ->build();

            // Reflect breaker transitions into the owm_breaker_state gauge. The gauge
            // lives in shared Redis, so whichever process observes the trip/calm keeps
            // every scrape target consistent.
            $ganesha->subscribe(function ($event) use ($app) {
                if ($event === Ganesha::EVENT_TRIPPED) {
                    $app->make(MetricsRecorder::class)->setBreakerOpen(true);
                } elseif ($event === Ganesha::EVENT_CALMED_DOWN) {
                    $app->make(MetricsRecorder::class)->setBreakerOpen(false);
                }
            });

            return $ganesha;
        });

        $this->app->singleton(OpenWeatherProvider::class, function ($app) {
            return new OpenWeatherProvider(
                $app->make(Ganesha::class),
                $app->make(MetricsRecorder::class),
            );
        });

        $this->app->singleton(ClimateProviderFactory::class, function ($app) {
            return new ClimateProviderFactory(
                $app->make(OpenWeatherProvider::class),
            );
        });

        $this->app->bind(IngestMeasurementsHandler::class, function ($app) {
            return new IngestMeasurementsHandler(
                $app->make(WeatherStationRepository::class),
                $app->make(ClimateProviderFactory::class),
                $app->make(EventPublisher::class),
                $app->make(LastReadingCache::class),
                $app->make(TracerInterface::class),
                $app->make(MetricsRecorder::class),
                config('services.queues.raw_measurements'),
            );
        });
    }

    public function boot(): void {}
}
