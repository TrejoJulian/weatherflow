<?php

declare(strict_types=1);

namespace App\Domain\WeatherStation\ValueObjects;

use InvalidArgumentException;

final class Location
{
    public function __construct(
        private readonly float $latitude,
        private readonly float $longitude,
    ) {
        if ($latitude < -90.0 || $latitude > 90.0) {
            throw new InvalidArgumentException("Invalid latitude: '{$latitude}'. Must be between -90 and 90.");
        }

        if ($longitude < -180.0 || $longitude > 180.0) {
            throw new InvalidArgumentException("Invalid longitude: '{$longitude}'. Must be between -180 and 180.");
        }
    }

    public function latitude(): float
    {
        return $this->latitude;
    }

    public function longitude(): float
    {
        return $this->longitude;
    }

    public function equals(self $other): bool
    {
        return $this->latitude === $other->latitude
            && $this->longitude === $other->longitude;
    }
}
