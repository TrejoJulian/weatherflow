<?php

declare(strict_types=1);

namespace App\Domain\Measurement\ValueObjects;

final class AtmosphericPressure
{
    public function __construct(
        private readonly float $value,
    ) {
        if ($value <= 0.0) {
            throw new \InvalidArgumentException("Invalid atmospheric pressure: '{$value}'. Must be greater than 0.");
        }
    }

    public function value(): float
    {
        return $this->value;
    }
}