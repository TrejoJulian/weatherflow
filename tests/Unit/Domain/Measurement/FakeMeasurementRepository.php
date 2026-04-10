<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Measurement;

use App\Domain\Measurement\Entities\Measurement;
use App\Domain\Measurement\Repositories\MeasurementRepository;
use App\Domain\Measurement\ValueObjects\MeasurementId;

final class FakeMeasurementRepository implements MeasurementRepository
{
    /** @var Measurement[] */
    private array $measurements = [];

    public function save(Measurement $measurement): void
    {
        $this->measurements[$measurement->id()->value()] = $measurement;
    }

    public function findById(MeasurementId $id): ?Measurement
    {
        return $this->measurements[$id->value()] ?? null;
    }

    public function findAll(): array
    {
        return array_values($this->measurements);
    }

    public function delete(MeasurementId $id): void
    {
        unset($this->measurements[$id->value()]);
    }

    public function seed(Measurement ...$measurements): void
    {
        foreach ($measurements as $measurement) {
            $this->measurements[$measurement->id()->value()] = $measurement;
        }
    }
}