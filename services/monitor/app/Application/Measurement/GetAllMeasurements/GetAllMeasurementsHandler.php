<?php

declare(strict_types=1);

namespace App\Application\Measurement\GetAllMeasurements;

use App\Application\Measurement\MeasurementResponse;
use App\Domain\Measurement\Entities\Measurement;
use App\Domain\Measurement\Enums\AlertType;
use App\Domain\Measurement\Repositories\MeasurementRepository;
use App\Domain\Measurement\ValueObjects\MeasurementFilters;

final class GetAllMeasurementsHandler
{
    public function __construct(
        private readonly MeasurementRepository $measurementRepository,
    ) {}

    /** @return MeasurementResponse[] */
    public function handle(GetAllMeasurementsQuery $query): array
    {
        $filters = new MeasurementFilters(
            stationName:  $query->stationName,
            tempMin:      $query->tempMin,
            tempMax:      $query->tempMax,
            alertOnly:    $query->alertOnly,
            alertType:    $query->alertType !== null ? AlertType::from($query->alertType) : null,
            dateFrom:     $query->dateFrom,
            dateTo:       $query->dateTo,
            humidityMin:  $query->humidityMin,
            humidityMax:  $query->humidityMax,
            pressureMin:  $query->pressureMin,
            pressureMax:  $query->pressureMax,
        );

        return array_map(
            fn(Measurement $measurement) => MeasurementResponse::fromEntity($measurement),
            $this->measurementRepository->findAll($filters),
        );
    }
}
