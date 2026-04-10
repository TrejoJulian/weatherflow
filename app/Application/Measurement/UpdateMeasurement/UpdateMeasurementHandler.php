<?php

declare(strict_types=1);

namespace App\Application\Measurement\UpdateMeasurement;

use App\Application\Measurement\MeasurementResponse;
use App\Domain\Measurement\Exceptions\MeasurementNotFoundException;
use App\Domain\Measurement\Repositories\MeasurementRepository;
use App\Domain\Measurement\ValueObjects\AtmosphericPressure;
use App\Domain\Measurement\ValueObjects\Humidity;
use App\Domain\Measurement\ValueObjects\MeasurementId;
use App\Domain\Measurement\ValueObjects\Temperature;
use DateTimeImmutable;

final class UpdateMeasurementHandler
{
    public function __construct(
        private readonly MeasurementRepository $measurementRepository,
    ) {}

    public function handle(UpdateMeasurementCommand $command): MeasurementResponse
    {
        $measurement = $this->measurementRepository->findById(
            MeasurementId::fromString($command->id),
        );

        if ($measurement === null) {
            throw new MeasurementNotFoundException($command->id);
        }

        $measurement->update(
            new Temperature($command->temperature),
            new Humidity($command->humidity),
            new AtmosphericPressure($command->atmosphericPressure),
            new DateTimeImmutable($command->reportedAt),
        );

        $this->measurementRepository->save($measurement);

        return MeasurementResponse::fromEntity($measurement);
    }
}