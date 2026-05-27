<?php

declare(strict_types=1);

use App\Domain\User\ValueObjects\UserId;
use App\Domain\WeatherStation\Entities\WeatherStation;
use App\Domain\WeatherStation\Enums\StationStatus;
use App\Domain\WeatherStation\ValueObjects\Location;
use App\Domain\WeatherStation\ValueObjects\StationId;

test('creates a station with correct data', function () {
    $station = WeatherStation::create(
        StationId::generate(),
        UserId::fromString('00000000-0000-4000-a000-000000000001'),
        'Estación Central',
        new Location(-34.6037, -58.3816),
        'Davis Vantage Pro2',
    );

    expect($station->stationName())->toBe('Estación Central')
        ->and($station->sensorModel())->toBe('Davis Vantage Pro2')
        ->and($station->status())->toBe(StationStatus::Active)
        ->and($station->id())->toBeInstanceOf(StationId::class);
});

test('defaults status to active', function () {
    $station = WeatherStation::create(
        StationId::generate(),
        UserId::fromString('00000000-0000-4000-a000-000000000001'),
        'Estación Central',
        new Location(-34.6037, -58.3816),
        'Davis Vantage Pro2',
    );

    expect($station->status())->toBe(StationStatus::Active);
});

test('defaults createdAt to approximately now', function () {
    $before = new \DateTimeImmutable();
    $station = WeatherStation::create(
        StationId::generate(),
        UserId::fromString('00000000-0000-4000-a000-000000000001'),
        'Estación Central',
        new Location(-34.6037, -58.3816),
        'Davis Vantage Pro2',
    );
    $after = new \DateTimeImmutable();

    expect($station->createdAt()->getTimestamp())->toBeGreaterThanOrEqual($before->getTimestamp())
        ->and($station->createdAt()->getTimestamp())->toBeLessThanOrEqual($after->getTimestamp());
});

test('preserves explicit createdAt value', function () {
    $createdAt = new \DateTimeImmutable('2026-03-15T12:00:00+00:00');
    $station = WeatherStation::create(
        StationId::generate(),
        UserId::fromString('00000000-0000-4000-a000-000000000001'),
        'Estación Central',
        new Location(-34.6037, -58.3816),
        'Davis Vantage Pro2',
        createdAt: $createdAt,
    );

    expect($station->createdAt())->toEqual($createdAt);
});

test('update does not change createdAt', function () {
    $createdAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
    $station = WeatherStation::create(
        StationId::generate(),
        UserId::fromString('00000000-0000-4000-a000-000000000001'),
        'Estación Central',
        new Location(-34.6037, -58.3816),
        'Davis Vantage Pro2',
        createdAt: $createdAt,
    );

    $station->update(
        UserId::fromString('00000000-0000-4000-a000-000000000002'),
        'Estación Norte',
        new Location(0.0, 0.0),
        'Sensor X',
        StationStatus::Inactive,
    );

    expect($station->createdAt())->toEqual($createdAt);
});

test('updates station data', function () {
    $id      = StationId::generate();
    $station = WeatherStation::create(
        $id,
        UserId::fromString('00000000-0000-4000-a000-000000000001'),
        'Estación Central',
        new Location(-34.6037, -58.3816),
        'Davis Vantage Pro2',
    );

    $newOwner = UserId::fromString('00000000-0000-4000-a000-000000000002');
    $station->update($newOwner, 'Estación Norte', new Location(0.0, 0.0), 'Sensor X', StationStatus::Inactive);

    expect($station->id()->equals($id))->toBeTrue()
        ->and($station->stationName())->toBe('Estación Norte')
        ->and($station->ownerId()->value())->toBe('00000000-0000-4000-a000-000000000002')
        ->and($station->status())->toBe(StationStatus::Inactive);
});