<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Contracts\EventPublisher;
use App\Domain\User\Repositories\UserRepository;
use App\Domain\WeatherStation\Repositories\WeatherStationRepository;
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
    }

    public function boot(): void {}
}
