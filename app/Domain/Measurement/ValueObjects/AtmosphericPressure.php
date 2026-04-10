<?php

declare(strict_types=1);

namespace App\Domain\Measurement\ValueObjects;

final class AtmosphericPressure
{
    public function __construct(
        private readonly float $value,
    ) {}

    public function value(): float
    {
        return $this->value;
    }
}