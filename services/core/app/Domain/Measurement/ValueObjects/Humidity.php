<?php

declare(strict_types=1);

namespace App\Domain\Measurement\ValueObjects;

use InvalidArgumentException;

final class Humidity
{
    public function __construct(
        private readonly float $value,
    ) {
        if ($value < 0.0 || $value > 100.0) {
            throw new InvalidArgumentException("Invalid humidity: '{$value}'. Must be between 0 and 100.");
        }
    }

    public function value(): float
    {
        return $this->value;
    }
}