<?php

declare(strict_types=1);

namespace App\Application\WeatherStation\DeleteStation;

use App\Domain\WeatherStation\Exceptions\StationNotFoundException;
use App\Domain\WeatherStation\Repositories\WeatherStationRepository;
use App\Domain\WeatherStation\ValueObjects\StationId;

final class DeleteStationHandler
{
    public function __construct(
        private readonly WeatherStationRepository $stationRepository,
    ) {}

    public function handle(DeleteStationCommand $command): void
    {
        $stationId = StationId::fromString($command->id);

        if ($this->stationRepository->findById($stationId) === null) {
            throw new StationNotFoundException($command->id);
        }

        $this->stationRepository->delete($stationId);
    }
}