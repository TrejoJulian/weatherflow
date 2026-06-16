<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Contracts\EventPublisher;
use App\Application\WeatherStation\UpdateStation\UpdateStationHandler;
use App\Domain\User\Repositories\UserRepository;
use App\Domain\WeatherStation\Repositories\WeatherStationRepository;
use App\Infrastructure\Http\Clients\ClimateProviderFactory;
use App\Infrastructure\Http\Clients\OpenWeatherProvider;
use App\Infrastructure\Messaging\RabbitMQEventPublisher;
use App\Infrastructure\Persistence\MongoDB\MongoUserRepository;
use App\Infrastructure\Persistence\MongoDB\MongoWeatherStationRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepository::class, MongoUserRepository::class);
        $this->app->bind(WeatherStationRepository::class, MongoWeatherStationRepository::class);
        $this->app->bind(EventPublisher::class, RabbitMQEventPublisher::class);

        $this->app->bind(UpdateStationHandler::class, function ($app) {
            return new UpdateStationHandler(
                $app->make(WeatherStationRepository::class),
                $app->make(UserRepository::class),
                $app->make(EventPublisher::class),
                config('services.queues.stations'),
            );
        });

        $this->app->singleton(OpenWeatherProvider::class);
        $this->app->singleton(ClimateProviderFactory::class, function ($app) {
            return new ClimateProviderFactory(
                $app->make(OpenWeatherProvider::class),
            );
        });
    }

    public function boot(): void {}
}
