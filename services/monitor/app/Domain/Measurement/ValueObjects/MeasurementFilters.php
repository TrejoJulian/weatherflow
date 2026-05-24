<?php

declare(strict_types=1);

namespace App\Domain\Measurement\ValueObjects;

use App\Domain\Measurement\Enums\AlertType;

final class MeasurementFilters
{
    public function __construct(
        private readonly ?string    $stationName = null,
        private readonly ?float     $tempMin     = null,
        private readonly ?float     $tempMax     = null,
        private readonly ?bool      $alertOnly   = null,
        private readonly ?AlertType $alertType   = null,
    ) {}

    public function stationName(): ?string
    {
        return $this->stationName;
    }

    public function tempMin(): ?float
    {
        return $this->tempMin;
    }

    public function tempMax(): ?float
    {
        return $this->tempMax;
    }

    public function alertOnly(): ?bool
    {
        return $this->alertOnly;
    }

    public function alertType(): ?AlertType
    {
        return $this->alertType;
    }
}
