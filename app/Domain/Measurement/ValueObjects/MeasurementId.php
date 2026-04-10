<?php

declare(strict_types=1);

namespace App\Domain\Measurement\ValueObjects;

use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

final class MeasurementId
{
    private function __construct(
        private readonly string $value,
    ) {}

    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());
    }

    public static function fromString(string $value): self
    {
        if (!Uuid::isValid($value)) {
            throw new InvalidArgumentException("Invalid MeasurementId: '{$value}'");
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}