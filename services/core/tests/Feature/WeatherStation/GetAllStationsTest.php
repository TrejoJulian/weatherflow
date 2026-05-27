<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\MongoDB\WeatherStationModel;
use Tests\Feature\RefreshMongoCollections;

uses(RefreshMongoCollections::class);

const STATION_ID_CENTRAL = '00000000-0000-4000-a000-000000000101';
const STATION_ID_NORTE   = '00000000-0000-4000-a000-000000000102';
const STATION_ID_OTHER   = '00000000-0000-4000-a000-000000000103';

beforeEach(function () {
    $this->collectionsToClean = ['weather_stations', 'users'];
    $this->cleanCollections();

    $this->ownerId = $this->postJson('/api/users', [
        'email'      => 'owner@example.com',
        'first_name' => 'Owner',
        'last_name'  => 'User',
    ])->json('id');

    seedStation(STATION_ID_CENTRAL, 'Estación Central', '2026-01-01T00:00:00+00:00');
    seedStation(STATION_ID_NORTE, 'Estación Norte', '2026-02-15T00:00:00+00:00');
    seedStation(STATION_ID_OTHER, 'Station XYZ', '2026-04-01T00:00:00+00:00');
});

function seedStation(string $id, string $name, string $createdAt): void
{
    WeatherStationModel::create([
        '_id'          => $id,
        'owner_id'     => test()->ownerId,
        'name'         => $name,
        'location'     => ['latitude' => 0.0, 'longitude' => 0.0],
        'sensor_model' => 'Sensor X',
        'status'       => 'active',
        'created_at'   => $createdAt,
    ]);
}

test('returns all stations with createdAt when no filters are provided', function () {
    $response = $this->getJson('/api/stations');

    $response->assertStatus(200)
        ->assertJsonCount(3);

    foreach ($response->json() as $station) {
        expect($station)->toHaveKey('createdAt')
            ->and($station['createdAt'])->not->toBeEmpty();
    }
});

test('filters stations by partial name', function () {
    $this->getJson('/api/stations?name=Central')
        ->assertStatus(200)
        ->assertJsonCount(1)
        ->assertJsonFragment(['id' => STATION_ID_CENTRAL, 'stationName' => 'Estación Central']);
});

test('name filter is case insensitive', function () {
    $lower = $this->getJson('/api/stations?name=central')->json();
    $upper = $this->getJson('/api/stations?name=Central')->json();

    expect($lower)->toEqual($upper);
});

test('returns empty array when name matches no stations', function () {
    $this->getJson('/api/stations?name=XYZ123')
        ->assertStatus(200)
        ->assertExactJson([]);
});

test('filters stations by createdFrom', function () {
    $this->getJson('/api/stations?created_from=2026-02-01')
        ->assertStatus(200)
        ->assertJsonCount(2)
        ->assertJsonMissing(['id' => STATION_ID_CENTRAL]);
});

test('filters stations by createdTo', function () {
    $this->getJson('/api/stations?created_to=2026-02-01')
        ->assertStatus(200)
        ->assertJsonCount(1)
        ->assertJsonFragment(['id' => STATION_ID_CENTRAL]);
});

test('filters stations by created date range', function () {
    $this->getJson('/api/stations?created_from=2026-02-01&created_to=2026-03-01')
        ->assertStatus(200)
        ->assertJsonCount(1)
        ->assertJsonFragment(['id' => STATION_ID_NORTE]);
});

test('combines name and date filters', function () {
    $this->getJson('/api/stations?name=Central&created_from=2025-12-01&created_to=2026-02-01')
        ->assertStatus(200)
        ->assertJsonCount(1)
        ->assertJsonFragment(['id' => STATION_ID_CENTRAL]);
});

test('returns 422 when created_to is before created_from', function () {
    $this->getJson('/api/stations?created_from=2026-04-10&created_to=2026-04-01')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['created_to']);
});

test('returns 422 when created_from is invalid', function () {
    $this->getJson('/api/stations?created_from=not-a-date')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['created_from']);
});

test('returns createdAt for legacy station without created_at in mongo', function () {
    WeatherStationModel::create([
        '_id'          => '00000000-0000-4000-a000-000000000199',
        'owner_id'     => $this->ownerId,
        'name'         => 'Legacy Station',
        'location'     => ['latitude' => 0.0, 'longitude' => 0.0],
        'sensor_model' => 'Sensor X',
        'status'       => 'active',
    ]);

    $response = $this->getJson('/api/stations/00000000-0000-4000-a000-000000000199');

    $response->assertStatus(200)
        ->assertJsonStructure(['createdAt']);

    expect($response->json('createdAt'))->not->toBeEmpty();
});
