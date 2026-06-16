<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\WeatherStation;

use App\Domain\WeatherStation\Clients\ClimateProvider;
use App\Domain\WeatherStation\ValueObjects\ClimateReading;
use App\Domain\WeatherStation\ValueObjects\Location;

final class FakeClimateProvider implements ClimateProvider
{
    public function __construct(
        private readonly ClimateReading $reading,
    ) {}

    public function fetchCurrentReading(Location $location): ClimateReading
    {
        return $this->reading;
    }
}
