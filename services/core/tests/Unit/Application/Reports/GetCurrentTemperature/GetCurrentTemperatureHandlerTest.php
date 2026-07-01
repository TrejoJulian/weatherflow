<?php

declare(strict_types=1);

use App\Application\Reports\GetCurrentTemperature\CurrentTemperatureResponse;
use App\Application\Reports\GetCurrentTemperature\GetCurrentTemperatureHandler;
use App\Application\Reports\GetCurrentTemperature\GetCurrentTemperatureQuery;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\WeatherStation\Entities\WeatherStation;
use App\Domain\WeatherStation\Exceptions\ClimateProviderUnavailableException;
use App\Domain\WeatherStation\Exceptions\NoCachedReadingAvailableException;
use App\Domain\WeatherStation\Exceptions\StationNotFoundException;
use App\Domain\WeatherStation\ValueObjects\ClimateReading;
use App\Domain\WeatherStation\ValueObjects\Location;
use App\Domain\WeatherStation\ValueObjects\StationId;
use App\Infrastructure\Http\Clients\ClimateProviderFactory;
use Tests\Unit\Domain\WeatherStation\FakeClimateProvider;
use Tests\Unit\Domain\WeatherStation\FakeWeatherStationRepository;
use Tests\Unit\Domain\WeatherStation\ThrowingFakeClimateProvider;
use Tests\Unit\Infrastructure\Cache\FakeLastReadingCache;

function seededStation(string $name = 'Universidad Nacional de Quilmes'): WeatherStation
{
    return WeatherStation::create(
        StationId::generate(),
        UserId::generate(),
        $name,
        new Location(-34.7064, -58.2797),
        'OpenWeatherMap API',
    );
}

test('serves a fresh cached reading without calling the provider', function () {
    $station = seededStation();
    $stationRepo = new FakeWeatherStationRepository();
    $stationRepo->seed($station);

    $cache = new FakeLastReadingCache;
    $cache->put($station->id(), new ClimateReading(19.0, 60.0, 1010.0, new DateTimeImmutable('2026-06-08T15:00:00Z')));

    $factory = new ClimateProviderFactory(new ThrowingFakeClimateProvider(new RuntimeException('should not be called')));

    $response = (new GetCurrentTemperatureHandler($stationRepo, $factory, $cache))
        ->handle(new GetCurrentTemperatureQuery($station->id()->value()));

    expect($response)->toBeInstanceOf(CurrentTemperatureResponse::class)
        ->and($response->temperature)->toBe(19.0)
        ->and($response->stale)->toBeFalse()
        ->and($response->source)->toBe('cache');
});

test('returns a live reading when there is no fresh cache', function () {
    $station = seededStation();
    $stationRepo = new FakeWeatherStationRepository();
    $stationRepo->seed($station);

    $reading = new ClimateReading(21.4, 70.0, 1012.0, new DateTimeImmutable('2026-06-08T15:00:00Z'));
    $factory = new ClimateProviderFactory(new FakeClimateProvider($reading));

    $response = (new GetCurrentTemperatureHandler($stationRepo, $factory, new FakeLastReadingCache))
        ->handle(new GetCurrentTemperatureQuery($station->id()->value()));

    expect($response->temperature)->toBe(21.4)
        ->and($response->stale)->toBeFalse()
        ->and($response->source)->toBe('live');
});

test('goes live when the fresh key expired but the fallback still exists', function () {
    $station = seededStation();
    $stationRepo = new FakeWeatherStationRepository();
    $stationRepo->seed($station);

    $cache = new FakeLastReadingCache;
    $cache->put($station->id(), new ClimateReading(10.0, 50.0, 1000.0, new DateTimeImmutable('2026-06-08T14:00:00Z')));
    $cache->expireFresh($station->id());

    $reading = new ClimateReading(21.4, 70.0, 1012.0, new DateTimeImmutable('2026-06-08T15:00:00Z'));
    $factory = new ClimateProviderFactory(new FakeClimateProvider($reading));

    $response = (new GetCurrentTemperatureHandler($stationRepo, $factory, $cache))
        ->handle(new GetCurrentTemperatureQuery($station->id()->value()));

    expect($response->temperature)->toBe(21.4)
        ->and($response->stale)->toBeFalse()
        ->and($response->source)->toBe('live');
});

test('serves the stale fallback when the provider is unavailable', function () {
    $station = seededStation();
    $stationRepo = new FakeWeatherStationRepository();
    $stationRepo->seed($station);

    $cache = new FakeLastReadingCache;
    $cache->put($station->id(), new ClimateReading(20.8, 55.0, 1008.0, new DateTimeImmutable('2026-06-08T14:50:00Z')));
    $cache->expireFresh($station->id());

    $factory = new ClimateProviderFactory(new ThrowingFakeClimateProvider(new ClimateProviderUnavailableException));

    $response = (new GetCurrentTemperatureHandler($stationRepo, $factory, $cache))
        ->handle(new GetCurrentTemperatureQuery($station->id()->value()));

    expect($response->temperature)->toBe(20.8)
        ->and($response->stale)->toBeTrue()
        ->and($response->source)->toBe('fallback-cache');
});

test('throws NoCachedReadingAvailableException when the provider fails and nothing is cached', function () {
    $station = seededStation();
    $stationRepo = new FakeWeatherStationRepository();
    $stationRepo->seed($station);

    $factory = new ClimateProviderFactory(new ThrowingFakeClimateProvider(new ClimateProviderUnavailableException));

    (new GetCurrentTemperatureHandler($stationRepo, $factory, new FakeLastReadingCache))
        ->handle(new GetCurrentTemperatureQuery($station->id()->value()));
})->throws(NoCachedReadingAvailableException::class);

test('throws when the station does not exist', function () {
    $reading = new ClimateReading(21.4, 70.0, 1012.0, new DateTimeImmutable('2026-06-08T15:00:00Z'));
    $factory = new ClimateProviderFactory(new FakeClimateProvider($reading));

    (new GetCurrentTemperatureHandler(new FakeWeatherStationRepository(), $factory, new FakeLastReadingCache))
        ->handle(new GetCurrentTemperatureQuery('00000000-0000-4000-a000-000000000000'));
})->throws(StationNotFoundException::class);

test('serializes to the documented snake_case contract', function () {
    $station = seededStation('Bariloche');
    $stationRepo = new FakeWeatherStationRepository();
    $stationRepo->seed($station);

    $reading = new ClimateReading(8.2, 55.0, 1008.0, new DateTimeImmutable('2026-06-08T15:00:00Z'));
    $factory = new ClimateProviderFactory(new FakeClimateProvider($reading));

    $response = (new GetCurrentTemperatureHandler($stationRepo, $factory, new FakeLastReadingCache))
        ->handle(new GetCurrentTemperatureQuery($station->id()->value()));

    expect($response->jsonSerialize())
        ->toBe([
            'station_id'   => $station->id()->value(),
            'station_name' => 'Bariloche',
            'temperature'  => 8.2,
            'reported_at'  => '2026-06-08T15:00:00+00:00',
            'stale'        => false,
            'source'       => 'live',
        ]);
});
