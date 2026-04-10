<?php

declare(strict_types=1);

use App\Application\WeatherStation\GetStation\GetStationHandler;
use App\Application\WeatherStation\GetStation\GetStationQuery;
use App\Application\WeatherStation\StationResponse;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\WeatherStation\Entities\WeatherStation;
use App\Domain\WeatherStation\Exceptions\StationNotFoundException;
use App\Domain\WeatherStation\ValueObjects\Location;
use App\Domain\WeatherStation\ValueObjects\StationId;
use Tests\Unit\Domain\WeatherStation\FakeWeatherStationRepository;

function makeStation(): WeatherStation
{
    return WeatherStation::create(
        StationId::generate(),
        UserId::fromString('00000000-0000-4000-a000-000000000001'),
        'Estación Central',
        new Location(-34.6037, -58.3816),
        'Davis Vantage Pro2',
    );
}

test('returns a station by id', function () {
    $repo = new FakeWeatherStationRepository();
    $station = makeStation();
    $repo->seed($station);

    $response = (new GetStationHandler($repo))->handle(new GetStationQuery($station->id()->value()));

    expect($response)->toBeInstanceOf(StationResponse::class)
        ->and($response->id)->toBe($station->id()->value());
});

test('throws when station does not exist', function () {
    (new GetStationHandler(new FakeWeatherStationRepository()))
        ->handle(new GetStationQuery('00000000-0000-4000-a000-000000000000'));
})->throws(StationNotFoundException::class);