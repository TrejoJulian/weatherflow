<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Contracts\EventPublisher;
use App\Application\Measurement\CreateMeasurement\CreateMeasurementHandler;
use App\Application\Messaging\RawMeasurementHandler;
use App\Domain\Measurement\Repositories\MeasurementRepository;
use App\Domain\WeatherStation\Clients\StationClient;
use App\Infrastructure\Http\Clients\CoreStationClient;
use App\Infrastructure\Messaging\RabbitMQEventPublisher;
use App\Infrastructure\Persistence\MongoDB\MongoMeasurementRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use OpenTelemetry\API\Trace\TracerInterface;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MeasurementRepository::class, MongoMeasurementRepository::class);
        $this->app->bind(StationClient::class, CoreStationClient::class);
        $this->app->bind(EventPublisher::class, RabbitMQEventPublisher::class);

        $this->app->bind(CreateMeasurementHandler::class, function ($app) {
            return new CreateMeasurementHandler(
                $app->make(MeasurementRepository::class),
                $app->make(StationClient::class),
                $app->make(EventPublisher::class),
                config('services.queues.alerts'),
            );
        });

        $this->app->bind(RawMeasurementHandler::class, function ($app) {
            return new RawMeasurementHandler(
                $app->make(MeasurementRepository::class),
                $app->make(EventPublisher::class),
                $app->make(TracerInterface::class),
                config('services.queues.alerts'),
            );
        });
    }

    public function boot(): void
    {
        Http::globalRequestMiddleware(
            fn($request) => $request->withHeader('Accept', 'application/json')
        );
    }
}
