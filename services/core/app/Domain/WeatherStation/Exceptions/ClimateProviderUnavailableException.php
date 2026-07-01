<?php

declare(strict_types=1);

namespace App\Domain\WeatherStation\Exceptions;

use RuntimeException;

final class ClimateProviderUnavailableException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Climate provider is temporarily unavailable (circuit breaker open).');
    }
}
