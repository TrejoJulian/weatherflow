<?php

declare(strict_types=1);

namespace App\Providers;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Builder;
use Ackintosh\Ganesha\Storage\Adapter\Redis as GaneshaRedisAdapter;
use App\Application\Contracts\EventPublisher;
use App\Application\Contracts\LastReadingCache;
use App\Application\IngestMeasurements\IngestMeasurementsHandler;
use App\Application\WeatherStation\UpdateStation\UpdateStationHandler;
use App\Domain\User\Repositories\UserRepository;
use App\Domain\WeatherStation\Repositories\WeatherStationRepository;
use App\Infrastructure\Cache\RedisLastReadingCache;
use App\Infrastructure\Http\Clients\ClimateProviderFactory;
use App\Infrastructure\Http\Clients\OpenWeatherProvider;
use App\Infrastructure\Messaging\RabbitMQEventPublisher;
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

        $this->app->bind(UpdateStationHandler::class, function ($app) {
            return new UpdateStationHandler(
                $app->make(WeatherStationRepository::class),
                $app->make(UserRepository::class),
                $app->make(EventPublisher::class),
                config('services.queues.stations'),
            );
        });

        $this->app->singleton(Ganesha::class, function () {
            $breakerThreshold = config('services.resilience.breaker_threshold');
            $breakerReset = config('services.resilience.breaker_reset');

            return Builder::withRateStrategy()
                ->adapter(new GaneshaRedisAdapter(Redis::connection()->client()))
                ->failureRateThreshold(100)
                ->minimumRequests($breakerThreshold)
                ->intervalToHalfOpen($breakerReset)
                ->timeWindow(max($breakerReset * 2, 60))
                ->build();
        });

        $this->app->singleton(OpenWeatherProvider::class, function ($app) {
            return new OpenWeatherProvider($app->make(Ganesha::class));
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
                config('services.queues.raw_measurements'),
            );
        });
    }

    public function boot(): void {}
}
