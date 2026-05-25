<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Contracts\EventPublisher;
use App\Domain\Measurement\Repositories\MeasurementRepository;
use App\Domain\WeatherStation\Clients\StationClient;
use App\Infrastructure\Http\Clients\CoreStationClient;
use App\Infrastructure\Messaging\RabbitMQEventPublisher;
use App\Infrastructure\Persistence\MongoDB\MongoMeasurementRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MeasurementRepository::class, MongoMeasurementRepository::class);
        $this->app->bind(StationClient::class, CoreStationClient::class);
        $this->app->bind(EventPublisher::class, RabbitMQEventPublisher::class);
    }

    public function boot(): void
    {
        Http::globalRequestMiddleware(
            fn($request) => $request->withHeader('Accept', 'application/json')
        );
    }
}
