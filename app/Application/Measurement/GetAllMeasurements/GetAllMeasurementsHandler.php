<?php

declare(strict_types=1);

namespace App\Application\Measurement\GetAllMeasurements;

use App\Application\Measurement\MeasurementResponse;
use App\Domain\Measurement\Entities\Measurement;
use App\Domain\Measurement\Enums\AlertType;
use App\Domain\Measurement\Repositories\MeasurementRepository;
use App\Domain\Measurement\ValueObjects\MeasurementFilters;
use App\Domain\WeatherStation\Entities\WeatherStation;
use App\Domain\WeatherStation\Repositories\WeatherStationRepository;

final class GetAllMeasurementsHandler
{
    public function __construct(
        private readonly MeasurementRepository    $measurementRepository,
        private readonly WeatherStationRepository $weatherStationRepository,
    ) {}

    /** @return MeasurementResponse[] */
    public function handle(GetAllMeasurementsQuery $query): array
    {
        $filters = new MeasurementFilters(
            stationIds: $this->resolveStationIds($query->stationName),
            tempMin:    $query->tempMin,
            tempMax:    $query->tempMax,
            alertOnly:  $query->alertOnly,
            alertType:  $query->alertType !== null ? AlertType::from($query->alertType) : null,
        );

        return array_map(
            fn(Measurement $measurement) => MeasurementResponse::fromEntity($measurement),
            $this->measurementRepository->findAll($filters),
        );
    }

    /** @return string[]|null */
    private function resolveStationIds(?string $stationName): ?array
    {
        if ($stationName === null) {
            return null;
        }

        return array_values(array_map(
            fn(WeatherStation $station) => $station->id()->value(),
            array_filter(
                $this->weatherStationRepository->findAll(),
                fn(WeatherStation $station) => stripos($station->stationName(), $stationName) !== false,
            ),
        ));
    }
}