<?php

declare(strict_types=1);

namespace App\Domain\Measurement\ValueObjects;

use App\Domain\Measurement\Enums\AlertType;

final class MeasurementFilters
{
    /**
     * @param string[]|null $stationIds  null = no filter; empty array = no stations matched (force empty result)
     */
    public function __construct(
        private readonly ?array $stationIds = null,
        private readonly ?float $tempMin = null,
        private readonly ?float $tempMax = null,
        private readonly ?bool $alertOnly = null,
        private readonly ?AlertType $alertType = null,
    ) {}

    /** @return string[]|null */
    public function stationIds(): ?array
    {
        return $this->stationIds;
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