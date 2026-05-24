<?php

declare(strict_types=1);

namespace App\Domain\WeatherStation\Clients;

use App\Domain\WeatherStation\StationSummary;
use App\Domain\WeatherStation\ValueObjects\StationId;

interface StationClient
{
    public function findById(StationId $id): ?StationSummary;
}
