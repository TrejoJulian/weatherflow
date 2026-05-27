<?php

declare(strict_types=1);

namespace App\Domain\WeatherStation\ValueObjects;

final class StationFilters
{
    public function __construct(
        private readonly ?string $name = null,
        private readonly ?string $createdFrom = null,
        private readonly ?string $createdTo = null,
    ) {}

    public function name(): ?string
    {
        return $this->name;
    }

    public function createdFrom(): ?string
    {
        return $this->createdFrom;
    }

    public function createdTo(): ?string
    {
        return $this->createdTo;
    }
}
