<?php

declare(strict_types=1);

namespace App\Application\Measurement\DeleteMeasurement;

final class DeleteMeasurementCommand
{
    public function __construct(
        public readonly string $id,
    ) {}
}