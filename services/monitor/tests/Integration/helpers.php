<?php

declare(strict_types=1);

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

function coreHttp(): PendingRequest
{
    return Http::baseUrl(config('services.core.url'))
        ->acceptJson();
}

function createUserInCore(): string
{
    $response = coreHttp()->post('/users', [
        'email'      => 'integration-' . uniqid('', true) . '@example.com',
        'first_name' => 'Integration',
        'last_name'  => 'User',
    ])->throw();

    return $response->json('id');
}

function createStationInCore(string $userId, string $name): string
{
    $response = coreHttp()->post('/stations', [
        'owner_id'     => $userId,
        'station_name' => $name,
        'latitude'     => -34.6037,
        'longitude'    => -58.3816,
        'sensor_model' => 'Davis Vantage Pro2',
    ])->throw();

    return $response->json('id');
}

function renameStationInCore(string $stationId, string $userId, string $newName): void
{
    $station = coreHttp()
        ->get("/stations/{$stationId}")
        ->throw()
        ->json();

    coreHttp()
        ->put("/stations/{$stationId}", [
            'owner_id'     => $userId,
            'station_name' => $newName,
            'latitude'     => $station['latitude'],
            'longitude'    => $station['longitude'],
            'sensor_model' => $station['sensorModel'],
            'status'       => $station['status'],
        ])
        ->throw();
}
