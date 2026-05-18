<?php

declare(strict_types=1);

namespace App\Application\Measurement\GetMeasurement;

use App\Application\Measurement\MeasurementResponse;
use App\Domain\Measurement\Exceptions\MeasurementNotFoundException;
use App\Domain\Measurement\Repositories\MeasurementRepository;
use App\Domain\Measurement\ValueObjects\MeasurementId;

final class GetMeasurementHandler
{
    public function __construct(
        private readonly MeasurementRepository $measurementRepository,
    ) {}

    public function handle(GetMeasurementQuery $query): MeasurementResponse
    {
        $measurement = $this->measurementRepository->findById(
            MeasurementId::fromString($query->id),
        );

        if ($measurement === null) {
            throw new MeasurementNotFoundException($query->id);
        }

        return MeasurementResponse::fromEntity($measurement);
    }
}