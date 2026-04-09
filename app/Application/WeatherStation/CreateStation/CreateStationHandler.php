<?php

declare(strict_types=1);

namespace App\Application\WeatherStation\CreateStation;

use App\Application\WeatherStation\StationResponse;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Domain\User\Repositories\UserRepository;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\WeatherStation\Entities\WeatherStation;
use App\Domain\WeatherStation\Enums\StationStatus;
use App\Domain\WeatherStation\Repositories\WeatherStationRepository;
use App\Domain\WeatherStation\ValueObjects\Location;
use App\Domain\WeatherStation\ValueObjects\StationId;

final class CreateStationHandler
{
    public function __construct(
        private readonly WeatherStationRepository $stationRepository,
        private readonly UserRepository           $userRepository,
    ) {}

    public function handle(CreateStationCommand $command): StationResponse
    {
        $ownerId = UserId::fromString($command->ownerId);

        if ($this->userRepository->findById($ownerId) === null) {
            throw new UserNotFoundException($command->ownerId);
        }

        $station = WeatherStation::create(
            StationId::generate(),
            $ownerId,
            $command->stationName,
            new Location($command->latitude, $command->longitude),
            $command->sensorModel,
            $command->status !== null ? StationStatus::from($command->status) : StationStatus::Active,
        );

        $this->stationRepository->save($station);

        return StationResponse::fromEntity($station);
    }
}