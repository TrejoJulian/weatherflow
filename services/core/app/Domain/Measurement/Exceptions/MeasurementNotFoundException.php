<?php

declare(strict_types=1);

namespace App\Domain\Measurement\Exceptions;

use RuntimeException;

final class MeasurementNotFoundException extends RuntimeException
{
    public function __construct(string $measurementId)
    {
        parent::__construct("Measurement not found: '{$measurementId}'");
    }
}