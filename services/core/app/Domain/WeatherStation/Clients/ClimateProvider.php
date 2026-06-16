<?php

declare(strict_types=1);

namespace App\Domain\WeatherStation\Clients;

use App\Domain\WeatherStation\ValueObjects\ClimateReading;
use App\Domain\WeatherStation\ValueObjects\Location;

interface ClimateProvider
{
    public function fetchCurrentReading(Location $location): ClimateReading;
}
