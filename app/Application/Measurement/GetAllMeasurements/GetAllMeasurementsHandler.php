<?php

declare(strict_types=1);

namespace App\Application\Measurement\GetAllMeasurements;

use App\Application\Measurement\MeasurementResponse;
use App\Domain\Measurement\Entities\Measurement;
use App\Domain\Measurement\Repositories\MeasurementRepository;

final class GetAllMeasurementsHandler
{
    public function __construct(
        private readonly MeasurementRepository $measurementRepository,
    ) {}

    /** @return MeasurementResponse[] */
    public function handle(GetAllMeasurementsQuery $query): array
    {
        return array_map(
            fn(Measurement $m) => MeasurementResponse::fromEntity($m),
            $this->measurementRepository->findAll(),
        );
    }
}