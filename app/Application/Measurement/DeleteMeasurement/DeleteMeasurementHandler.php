<?php

declare(strict_types=1);

namespace App\Application\Measurement\DeleteMeasurement;

use App\Domain\Measurement\Exceptions\MeasurementNotFoundException;
use App\Domain\Measurement\Repositories\MeasurementRepository;
use App\Domain\Measurement\ValueObjects\MeasurementId;

final class DeleteMeasurementHandler
{
    public function __construct(
        private readonly MeasurementRepository $measurementRepository,
    ) {}

    public function handle(DeleteMeasurementCommand $command): void
    {
        $id = MeasurementId::fromString($command->id);

        if ($this->measurementRepository->findById($id) === null) {
            throw new MeasurementNotFoundException($command->id);
        }

        $this->measurementRepository->delete($id);
    }
}