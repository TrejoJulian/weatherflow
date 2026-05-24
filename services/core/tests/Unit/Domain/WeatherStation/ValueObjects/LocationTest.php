<?php

declare(strict_types=1);

use App\Domain\WeatherStation\ValueObjects\Location;

test('creates a location with valid coordinates', function () {
    $location = new Location(-34.6037, -58.3816);

    expect($location->latitude())->toBe(-34.6037)
        ->and($location->longitude())->toBe(-58.3816);
});

test('accepts boundary values', function () {
    expect(new Location(-90.0, -180.0))->toBeInstanceOf(Location::class)
        ->and(new Location(90.0, 180.0))->toBeInstanceOf(Location::class);
});

test('throws on invalid latitude', function () {
    new Location(91.0, 0.0);
})->throws(InvalidArgumentException::class);

test('throws on invalid longitude', function () {
    new Location(0.0, 181.0);
})->throws(InvalidArgumentException::class);

test('equals returns true for same coordinates', function () {
    $a = new Location(-34.6037, -58.3816);
    $b = new Location(-34.6037, -58.3816);

    expect($a->equals($b))->toBeTrue();
});

test('equals returns false for different coordinates', function () {
    $a = new Location(-34.6037, -58.3816);
    $b = new Location(0.0, 0.0);

    expect($a->equals($b))->toBeFalse();
});
