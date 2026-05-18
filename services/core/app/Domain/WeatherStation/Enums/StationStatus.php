<?php

declare(strict_types=1);

namespace App\Domain\WeatherStation\Enums;

enum StationStatus: string
{
    case Active   = 'active';
    case Inactive = 'inactive';
}
