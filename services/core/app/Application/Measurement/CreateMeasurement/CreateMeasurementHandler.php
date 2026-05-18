<?php

declare(strict_types=1);

namespace App\Application\Measurement\CreateMeasurement;

use App\Application\Measurement\MeasurementResponse;
use App\Domain\Measurement\Entities\Measurement;
use App\Domain\Measurement\Repositories\MeasurementRepository;
use App\Domain\Measurement\ValueObjects\AtmosphericPressure;
use App\Domain\Measurement\ValueObjects\Humidity;
use App\Domain\Measurement\ValueObjects\MeasurementId;
use App\Domain\Measurement\ValueObjects\Temperature;
use App\Domain\WeatherStation\Exceptions\StationNotFoundException;
use App\Domain\WeatherStation\Repositories\WeatherStationRepository;
use App\Domain\WeatherStation\ValueObjects\StationId;
use DateTimeImmutable;

final class CreateMeasurementHandler
{
    public function __construct(
        private readonly MeasurementRepository    $measurementRepository,
        private readonly WeatherStationRepository $stationRepository,
    ) {}

    public function handle(CreateMeasurementCommand $command): MeasurementResponse
    {
        $stationId = StationId::fromString($command->stationId);

        if ($this->stationRepository->findById($stationId) === null) {
            throw new StationNotFoundException($command->stationId);
        }

        $measurement = Measurement::create(
            MeasurementId::generate(),
            $stationId,
            new Temperature($command->temperature),
            new Humidity($command->humidity),
            new AtmosphericPressure($command->atmosphericPressure),
            new DateTimeImmutable($command->reportedAt),
        );

        $this->measurementRepository->save($measurement);

        return MeasurementResponse::fromEntity($measurement);
    }
}