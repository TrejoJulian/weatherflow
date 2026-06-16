<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\User\Entities\User;
use App\Domain\User\Repositories\UserRepository;
use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\WeatherStation\Entities\WeatherStation;
use App\Domain\WeatherStation\Enums\ClimateProviderType;
use App\Domain\WeatherStation\Enums\StationStatus;
use App\Domain\WeatherStation\Repositories\WeatherStationRepository;
use App\Domain\WeatherStation\ValueObjects\Location;
use App\Domain\WeatherStation\ValueObjects\StationId;
use Illuminate\Database\Seeder;

final class DefaultStationsSeeder extends Seeder
{
    private const SYSTEM_USER_ID = '00000000-0000-4000-8000-000000000001';

    /**
     * Fixed station UUIDs keep the seeder idempotent: persisting through the
     * domain repositories performs an updateOrCreate by _id, so re-running
     * never duplicates rows.
     *
     * @var list<array{id: string, name: string, latitude: float, longitude: float}>
     */
    private const STATIONS = [
        [
            'id'        => '00000000-0000-4000-8000-000000000101',
            'name'      => 'Universidad Nacional de Quilmes',
            'latitude'  => -34.7064,
            'longitude' => -58.2797,
        ],
        [
            'id'        => '00000000-0000-4000-8000-000000000102',
            'name'      => 'Bariloche',
            'latitude'  => -41.1335,
            'longitude' => -71.3103,
        ],
        [
            'id'        => '00000000-0000-4000-8000-000000000103',
            'name'      => 'Ushuaia',
            'latitude'  => -54.8019,
            'longitude' => -68.3030,
        ],
    ];

    public function run(UserRepository $users, WeatherStationRepository $stations): void
    {
        $systemUserId = UserId::fromString(self::SYSTEM_USER_ID);

        $users->save(User::create(
            $systemUserId,
            new Email('system@weatherflow.local'),
            'System',
            'Owner',
        ));

        foreach (self::STATIONS as $station) {
            $stations->save(WeatherStation::create(
                StationId::fromString($station['id']),
                $systemUserId,
                $station['name'],
                new Location($station['latitude'], $station['longitude']),
                'OpenWeatherMap API'
            ));
        }
    }
}
