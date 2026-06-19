<?php

declare(strict_types=1);

use App\Application\Reports\GetCurrentTemperature\CurrentTemperatureResponse;
use App\Application\Reports\GetCurrentTemperature\GetCurrentTemperatureHandler;
use App\Application\Reports\GetCurrentTemperature\GetCurrentTemperatureQuery;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\WeatherStation\Entities\WeatherStation;
use App\Domain\WeatherStation\Exceptions\StationNotFoundException;
use App\Domain\WeatherStation\ValueObjects\ClimateReading;
use App\Domain\WeatherStation\ValueObjects\Location;
use App\Domain\WeatherStation\ValueObjects\StationId;
use App\Infrastructure\Http\Clients\ClimateProviderFactory;
use Tests\Unit\Domain\WeatherStation\FakeClimateProvider;
use Tests\Unit\Domain\WeatherStation\FakeWeatherStationRepository;

test('returns the current temperature for an existing station', function () {
    $station = WeatherStation::create(
        StationId::generate(),
        UserId::generate(),
        'Universidad Nacional de Quilmes',
        new Location(-34.7064, -58.2797),
        'OpenWeatherMap API',
    );

    $stationRepo = new FakeWeatherStationRepository();
    $stationRepo->seed($station);

    $reading = new ClimateReading(21.4, 70.0, 1012.0, new DateTimeImmutable('2026-06-08T15:00:00Z'));
    $factory = new ClimateProviderFactory(new FakeClimateProvider($reading));

    $response = (new GetCurrentTemperatureHandler($stationRepo, $factory))
        ->handle(new GetCurrentTemperatureQuery($station->id()->value()));

    expect($response)->toBeInstanceOf(CurrentTemperatureResponse::class)
        ->and($response->stationId)->toBe($station->id()->value())
        ->and($response->stationName)->toBe('Universidad Nacional de Quilmes')
        ->and($response->temperature)->toBe(21.4);
});

test('throws when the station does not exist', function () {
    $reading = new ClimateReading(21.4, 70.0, 1012.0, new DateTimeImmutable('2026-06-08T15:00:00Z'));
    $factory = new ClimateProviderFactory(new FakeClimateProvider($reading));

    (new GetCurrentTemperatureHandler(new FakeWeatherStationRepository(), $factory))
        ->handle(new GetCurrentTemperatureQuery('00000000-0000-4000-a000-000000000000'));
})->throws(StationNotFoundException::class);

test('serializes to the documented snake_case contract', function () {
    $station = WeatherStation::create(
        StationId::generate(),
        UserId::generate(),
        'Bariloche',
        new Location(-41.1335, -71.3103),
        'OpenWeatherMap API',
    );

    $stationRepo = new FakeWeatherStationRepository();
    $stationRepo->seed($station);

    $reading = new ClimateReading(8.2, 55.0, 1008.0, new DateTimeImmutable('2026-06-08T15:00:00Z'));
    $factory = new ClimateProviderFactory(new FakeClimateProvider($reading));

    $response = (new GetCurrentTemperatureHandler($stationRepo, $factory))
        ->handle(new GetCurrentTemperatureQuery($station->id()->value()));

    expect($response->jsonSerialize())
        ->toBe([
            'station_id'   => $station->id()->value(),
            'station_name' => 'Bariloche',
            'temperature'  => 8.2,
            'reported_at'  => '2026-06-08T15:00:00+00:00',
        ]);
});
