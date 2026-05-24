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
use App\Domain\WeatherStation\Clients\StationClient;
use App\Domain\WeatherStation\Exceptions\StationNotFoundException;
use App\Domain\WeatherStation\ValueObjects\StationId;
use DateTimeImmutable;

final class CreateMeasurementHandler
{
    public function __construct(
        private readonly MeasurementRepository $measurementRepository,
        private readonly StationClient $stationClient,
    ) {}

    public function handle(CreateMeasurementCommand $command): MeasurementResponse
    {
        $stationId = StationId::fromString($command->stationId);

        $stationSummary = $this->stationClient->findById($stationId);

        if ($stationSummary === null) {
            throw new StationNotFoundException($command->stationId);
        }

        $measurement = Measurement::create(
            id:                  MeasurementId::generate(),
            stationId:           $stationId,
            stationName:         $stationSummary->stationName,
            temperature:         new Temperature($command->temperature),
            humidity:            new Humidity($command->humidity),
            atmosphericPressure: new AtmosphericPressure($command->atmosphericPressure),
            reportedAt:          new DateTimeImmutable($command->reportedAt),
        );

        $this->measurementRepository->save($measurement);

        return MeasurementResponse::fromEntity($measurement);
    }
}
