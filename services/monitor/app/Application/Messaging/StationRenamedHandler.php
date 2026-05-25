<?php

declare(strict_types=1);

namespace App\Application\Messaging;

use App\Domain\Measurement\Repositories\MeasurementRepository;
use App\Domain\WeatherStation\ValueObjects\StationId;

final class StationRenamedHandler
{
    public function __construct(
        private readonly MeasurementRepository $measurementRepository,
    ) {}

    public function handle(array $payload): void
    {
        $this->measurementRepository->updateStationNameByStationId(
            StationId::fromString($payload['station_id']),
            $payload['new_name'],
        );
    }
}
