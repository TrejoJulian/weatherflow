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
use App\Domain\WeatherStation\ValueObjects\StationId;
use DateTimeImmutable;

final class CreateMeasurementHandler
{
    public function __construct(
        private readonly MeasurementRepository $measurementRepository,
    ) {}

    public function handle(CreateMeasurementCommand $command): MeasurementResponse
    {
        $measurement = Measurement::create(
            id:                  MeasurementId::generate(),
            stationId:           StationId::fromString($command->stationId),
            stationName:         '', // temporary until StationClient is implemented
            temperature:         new Temperature($command->temperature),
            humidity:            new Humidity($command->humidity),
            atmosphericPressure: new AtmosphericPressure($command->atmosphericPressure),
            reportedAt:          new DateTimeImmutable($command->reportedAt),
        );

        $this->measurementRepository->save($measurement);

        return MeasurementResponse::fromEntity($measurement);
    }
}
