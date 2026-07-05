<?php

declare(strict_types=1);

use App\Application\Contracts\EventPublisher;
use App\Application\IngestMeasurements\IngestMeasurementsHandler;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\WeatherStation\Clients\ClimateProvider;
use App\Domain\WeatherStation\Entities\WeatherStation;
use App\Domain\WeatherStation\ValueObjects\ClimateReading;
use App\Domain\WeatherStation\ValueObjects\Location;
use App\Domain\WeatherStation\ValueObjects\StationId;
use App\Infrastructure\Http\Clients\ClimateProviderFactory;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\TracerInterface;
use Tests\TestCase;
use Tests\Unit\Domain\WeatherStation\FakeClimateProvider;
use Tests\Unit\Domain\WeatherStation\FakeWeatherStationRepository;
use Tests\Unit\Infrastructure\Cache\FakeLastReadingCache;
use Tests\Unit\Infrastructure\Cache\FaultyLastReadingCache;
use Tests\Unit\Infrastructure\Messaging\FakeEventPublisher;

uses(TestCase::class);

function ingestHandlerTracer(): TracerInterface
{
    return Globals::tracerProvider()->getTracer('test');
}

test('caches the reading and publishes the measurement on a successful tick', function () {
    $repository = new FakeWeatherStationRepository;
    $station = WeatherStation::create(
        StationId::generate(),
        UserId::fromString('00000000-0000-4000-a000-000000000001'),
        'Estación Central',
        new Location(-34.9, -58.3),
        'Sensor 1',
    );
    $repository->seed($station);

    $reading = new ClimateReading(21.4, 70.0, 1012.0, new DateTimeImmutable);
    $factory = new ClimateProviderFactory(new FakeClimateProvider($reading));
    $publisher = new FakeEventPublisher;
    $cache = new FakeLastReadingCache;

    (new IngestMeasurementsHandler($repository, $factory, $publisher, $cache, ingestHandlerTracer(), 'raw-measurements'))->handle();

    expect($cache->wasPut($station->id()))->toBeTrue()
        ->and($cache->get($station->id()))->toBe($reading)
        ->and($publisher->wasPublishedTo('raw-measurements'))->toBeTrue();
});

test('a cache write failure does not abort ingestion or publishing', function () {
    $repository = new FakeWeatherStationRepository;
    $station = WeatherStation::create(
        StationId::generate(),
        UserId::fromString('00000000-0000-4000-a000-000000000001'),
        'Estación Central',
        new Location(-34.9, -58.3),
        'Sensor 1',
    );
    $repository->seed($station);

    $reading = new ClimateReading(21.4, 70.0, 1012.0, new DateTimeImmutable);
    $factory = new ClimateProviderFactory(new FakeClimateProvider($reading));
    $publisher = new FakeEventPublisher;

    (new IngestMeasurementsHandler($repository, $factory, $publisher, new FaultyLastReadingCache, ingestHandlerTracer(), 'raw-measurements'))->handle();

    expect($publisher->wasPublishedTo('raw-measurements'))->toBeTrue();
});

test('does not cache or publish when the provider fails', function () {
    $repository = new FakeWeatherStationRepository;
    $station = WeatherStation::create(
        StationId::generate(),
        UserId::fromString('00000000-0000-4000-a000-000000000001'),
        'Estación Central',
        new Location(-34.9, -58.3),
        'Sensor 1',
    );
    $repository->seed($station);

    $failingProvider = new class implements ClimateProvider
    {
        public function fetchCurrentReading(Location $location): ClimateReading
        {
            throw new RuntimeException('OWM is down');
        }
    };
    $factory = new ClimateProviderFactory($failingProvider);
    $publisher = new FakeEventPublisher;
    $cache = new FakeLastReadingCache;

    (new IngestMeasurementsHandler($repository, $factory, $publisher, $cache, ingestHandlerTracer(), 'raw-measurements'))->handle();

    expect($cache->getPutCount())->toBe(0)
        ->and($publisher->wasPublishedTo('raw-measurements'))->toBeFalse();
});

test('a provider failure on one station does not stop caching and publishing the others', function () {
    $repository = new FakeWeatherStationRepository;
    $ownerId = UserId::fromString('00000000-0000-4000-a000-000000000001');
    $failingStation = WeatherStation::create(StationId::generate(), $ownerId, 'Falla', new Location(-1.0, 0.0), 'Sensor 1');
    $workingStation = WeatherStation::create(StationId::generate(), $ownerId, 'Anda', new Location(0.0, 0.0), 'Sensor 2');
    $repository->seed($failingStation, $workingStation);

    $reading = new ClimateReading(21.4, 70.0, 1012.0, new DateTimeImmutable);
    $provider = new class($reading) implements ClimateProvider
    {
        public function __construct(private readonly ClimateReading $reading) {}

        public function fetchCurrentReading(Location $location): ClimateReading
        {
            if ($location->latitude() === -1.0) {
                throw new RuntimeException('OWM is down for this station');
            }

            return $this->reading;
        }
    };
    $factory = new ClimateProviderFactory($provider);
    $publisher = new FakeEventPublisher;
    $cache = new FakeLastReadingCache;

    (new IngestMeasurementsHandler($repository, $factory, $publisher, $cache, ingestHandlerTracer(), 'raw-measurements'))->handle();

    expect($cache->wasPut($workingStation->id()))->toBeTrue()
        ->and($cache->wasPut($failingStation->id()))->toBeFalse()
        ->and($cache->getPutCount())->toBe(1)
        ->and($publisher->getPublishedTo('raw-measurements'))->toHaveCount(1);
});

test('a publish failure on one station does not stop publishing the others', function () {
    $repository = new FakeWeatherStationRepository;
    $ownerId = UserId::fromString('00000000-0000-4000-a000-000000000001');
    $unreachableStation = WeatherStation::create(StationId::generate(), $ownerId, 'Broker caído', new Location(-34.9, -58.3), 'Sensor 1');
    $workingStation = WeatherStation::create(StationId::generate(), $ownerId, 'Anda', new Location(-31.4, -64.2), 'Sensor 2');
    $repository->seed($unreachableStation, $workingStation);

    $reading = new ClimateReading(21.4, 70.0, 1012.0, new DateTimeImmutable);
    $factory = new ClimateProviderFactory(new FakeClimateProvider($reading));
    $cache = new FakeLastReadingCache;

    // Simulates RabbitMQ being unreachable for one station: publishing that
    // measurement throws (as AMQPStreamConnection would after connection_timeout).
    $publisher = new class($unreachableStation->id()->value()) implements EventPublisher
    {
        /** @var array<array{queue: string, payload: array}> */
        public array $published = [];

        public function __construct(private readonly string $failingStationId) {}

        public function publish(string $queue, array $payload): void
        {
            if (($payload['station_id'] ?? null) === $this->failingStationId) {
                throw new RuntimeException('RabbitMQ is unreachable');
            }

            $this->published[] = ['queue' => $queue, 'payload' => $payload];
        }
    };

    (new IngestMeasurementsHandler($repository, $factory, $publisher, $cache, ingestHandlerTracer(), 'raw-measurements'))->handle();

    expect($publisher->published)->toHaveCount(1)
        ->and($publisher->published[0]['payload']['station_id'])->toBe($workingStation->id()->value());
});
