<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\WeatherStation;

use App\Domain\WeatherStation\Clients\ClimateProvider;
use App\Domain\WeatherStation\ValueObjects\ClimateReading;
use App\Domain\WeatherStation\ValueObjects\Location;
use Throwable;

final class ThrowingFakeClimateProvider implements ClimateProvider
{
    public function __construct(
        private readonly Throwable $exception,
    ) {}

    public function fetchCurrentReading(Location $location): ClimateReading
    {
        throw $this->exception;
    }
}
