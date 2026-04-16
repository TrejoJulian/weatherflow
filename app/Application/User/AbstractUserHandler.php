<?php

declare(strict_types=1);

namespace App\Application\User;

use App\Domain\User\Entities\User;
use App\Domain\User\Repositories\UserRepository;
use App\Domain\WeatherStation\Entities\WeatherStation;
use App\Domain\WeatherStation\Repositories\WeatherStationRepository;

abstract class AbstractUserHandler
{
    public function __construct(
        protected readonly UserRepository           $userRepository,
        protected readonly WeatherStationRepository $stationRepository,
    ) {}

    /** @return array<string, WeatherStation> */
    protected function resolveSubscribedStationsById(User $user): array
    {
        $subscribedStationsById = [];

        foreach ($this->stationRepository->findByIds($user->subscriptions()) as $station) {
            $subscribedStationsById[$station->id()->value()] = $station;
        }

        return $subscribedStationsById;
    }
}