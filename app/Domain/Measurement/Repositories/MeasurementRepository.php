<?php

declare(strict_types=1);

namespace App\Domain\Measurement\Repositories;

use App\Domain\Measurement\Entities\Measurement;
use App\Domain\Measurement\ValueObjects\MeasurementId;

interface MeasurementRepository
{
    public function save(Measurement $measurement): void;

    public function findById(MeasurementId $id): ?Measurement;

    public function findAll(): array;

    public function delete(MeasurementId $id): void;
}