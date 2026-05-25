<?php

declare(strict_types=1);

namespace App\Application\WeatherStation\UpdateStation;

use App\Application\Contracts\EventPublisher;
use App\Application\WeatherStation\StationResponse;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Domain\User\Repositories\UserRepository;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\WeatherStation\Enums\StationStatus;
use App\Domain\WeatherStation\Exceptions\StationNotFoundException;
use App\Domain\WeatherStation\Repositories\WeatherStationRepository;
use App\Domain\WeatherStation\ValueObjects\Location;
use App\Domain\WeatherStation\ValueObjects\StationId;

final class UpdateStationHandler
{
    public function __construct(
        private readonly WeatherStationRepository $stationRepository,
        private readonly UserRepository           $userRepository,
        private readonly EventPublisher           $eventPublisher,
    ) {}

    public function handle(UpdateStationCommand $command): StationResponse
    {
        $station = $this->stationRepository->findById(StationId::fromString($command->id));

        if ($station === null) {
            throw new StationNotFoundException($command->id);
        }

        $ownerId = UserId::fromString($command->ownerId);

        if ($this->userRepository->findById($ownerId) === null) {
            throw new UserNotFoundException($command->ownerId);
        }

        $oldName = $station->stationName();

        $station->update(
            $ownerId,
            $command->stationName,
            new Location($command->latitude, $command->longitude),
            $command->sensorModel,
            StationStatus::from($command->status),
        );

        if ($oldName !== $command->stationName) {
            $this->eventPublisher->publish('station-events', [
                'event'      => 'StationRenamed',
                'station_id' => $station->id()->value(),
                'new_name'   => $command->stationName,
            ]);
        }

        $this->stationRepository->save($station);

        return StationResponse::fromEntity($station);
    }
}
