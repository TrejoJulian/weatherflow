<?php

declare(strict_types=1);

use Tests\Feature\RefreshMongoCollections;

uses(RefreshMongoCollections::class);

beforeEach(function () {
    $this->collectionsToClean = ['weather_stations', 'users', 'measurements'];
    $this->cleanCollections();
});

function createUser($test): string
{
    return $test->postJson('/api/users', [
        'email'      => 'owner@example.com',
        'first_name' => 'Owner',
        'last_name'  => 'User',
    ])->json('id');
}

// -------------------------------------------------------------------------
// POST /api/stations
// -------------------------------------------------------------------------

test('creates a station and returns 201', function () {
    $ownerId = createUser($this);

    $response = $this->postJson('/api/stations', [
        'owner_id'     => $ownerId,
        'station_name' => 'Estación Central',
        'latitude'     => -34.6037,
        'longitude'    => -58.3816,
        'sensor_model' => 'Davis Vantage Pro2',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['id', 'ownerId', 'stationName', 'latitude', 'longitude', 'sensorModel', 'status'])
        ->assertJsonFragment([
            'stationName' => 'Estación Central',
            'status'      => 'active',
        ]);
});

test('returns 422 when owner does not exist', function () {
    $this->postJson('/api/stations', [
        'owner_id'     => '00000000-0000-4000-a000-000000000000',
        'station_name' => 'Estación Central',
        'latitude'     => -34.6037,
        'longitude'    => -58.3816,
        'sensor_model' => 'Davis Vantage Pro2',
    ])->assertStatus(422);
});

test('returns 422 when required fields are missing', function () {
    $this->postJson('/api/stations', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['owner_id', 'station_name', 'latitude', 'longitude', 'sensor_model']);
});

test('creates a station with explicit inactive status', function () {
    $ownerId = createUser($this);

    $this->postJson('/api/stations', [
        'owner_id'     => $ownerId,
        'station_name' => 'Estación Inactiva',
        'latitude'     => 0.0,
        'longitude'    => 0.0,
        'sensor_model' => 'Sensor X',
        'status'       => 'inactive',
    ])->assertStatus(201)->assertJsonFragment(['status' => 'inactive']);
});

// -------------------------------------------------------------------------
// GET /api/stations/{id}
// -------------------------------------------------------------------------

test('returns a station by id', function () {
    $ownerId = createUser($this);

    $created = $this->postJson('/api/stations', [
        'owner_id'     => $ownerId,
        'station_name' => 'Estación Central',
        'latitude'     => -34.6037,
        'longitude'    => -58.3816,
        'sensor_model' => 'Davis Vantage Pro2',
    ])->json();

    $this->getJson("/api/stations/{$created['id']}")
        ->assertStatus(200)
        ->assertJsonFragment(['stationName' => 'Estación Central']);
});

test('returns 404 when station does not exist', function () {
    $this->getJson('/api/stations/00000000-0000-4000-a000-000000000000')
        ->assertStatus(404);
});

// -------------------------------------------------------------------------
// GET /api/stations
// -------------------------------------------------------------------------

test('returns all stations', function () {
    $ownerId = createUser($this);

    $this->postJson('/api/stations', [
        'owner_id' => $ownerId, 'station_name' => 'A', 'latitude' => 0.0, 'longitude' => 0.0, 'sensor_model' => 'S1',
    ]);
    $this->postJson('/api/stations', [
        'owner_id' => $ownerId, 'station_name' => 'B', 'latitude' => 1.0, 'longitude' => 1.0, 'sensor_model' => 'S2',
    ]);

    $this->getJson('/api/stations')
        ->assertStatus(200)
        ->assertJsonCount(2);
});

// -------------------------------------------------------------------------
// PUT /api/stations/{id}
// -------------------------------------------------------------------------

test('updates a station', function () {
    $ownerId = createUser($this);

    $created = $this->postJson('/api/stations', [
        'owner_id'     => $ownerId,
        'station_name' => 'Estación Central',
        'latitude'     => -34.6037,
        'longitude'    => -58.3816,
        'sensor_model' => 'Davis Vantage Pro2',
    ])->json();

    $this->putJson("/api/stations/{$created['id']}", [
        'owner_id'     => $ownerId,
        'station_name' => 'Estación Actualizada',
        'latitude'     => 0.0,
        'longitude'    => 0.0,
        'sensor_model' => 'Sensor Nuevo',
        'status'       => 'inactive',
    ])->assertStatus(200)->assertJsonFragment([
        'stationName' => 'Estación Actualizada',
        'status'      => 'inactive',
    ]);
});

test('returns 404 when updating nonexistent station', function () {
    $ownerId = createUser($this);

    $this->putJson('/api/stations/00000000-0000-4000-a000-000000000000', [
        'owner_id'     => $ownerId,
        'station_name' => 'X',
        'latitude'     => 0.0,
        'longitude'    => 0.0,
        'sensor_model' => 'X',
        'status'       => 'active',
    ])->assertStatus(404);
});

test('returns 422 when updating with nonexistent owner', function () {
    $ownerId = createUser($this);

    $created = $this->postJson('/api/stations', [
        'owner_id'     => $ownerId,
        'station_name' => 'Estación Central',
        'latitude'     => 0.0,
        'longitude'    => 0.0,
        'sensor_model' => 'Sensor',
    ])->json();

    $this->putJson("/api/stations/{$created['id']}", [
        'owner_id'     => '00000000-0000-4000-a000-000000000000',
        'station_name' => 'X',
        'latitude'     => 0.0,
        'longitude'    => 0.0,
        'sensor_model' => 'X',
        'status'       => 'active',
    ])->assertStatus(422);
});

// -------------------------------------------------------------------------
// DELETE /api/stations/{id}
// -------------------------------------------------------------------------

test('deletes a station and returns 204', function () {
    $ownerId = createUser($this);

    $created = $this->postJson('/api/stations', [
        'owner_id'     => $ownerId,
        'station_name' => 'Estación Central',
        'latitude'     => -34.6037,
        'longitude'    => -58.3816,
        'sensor_model' => 'Davis Vantage Pro2',
    ])->json();

    $this->deleteJson("/api/stations/{$created['id']}")->assertStatus(204);
    $this->getJson("/api/stations/{$created['id']}")->assertStatus(404);
});

test('returns 404 when deleting nonexistent station', function () {
    $this->deleteJson('/api/stations/00000000-0000-4000-a000-000000000000')
        ->assertStatus(404);
});

test('returns 409 when deleting a station that has measurements', function () {
    $ownerId = createUser($this);

    $station = $this->postJson('/api/stations', [
        'owner_id'     => $ownerId,
        'station_name' => 'Estación Central',
        'latitude'     => -34.6037,
        'longitude'    => -58.3816,
        'sensor_model' => 'Davis Vantage Pro2',
    ])->json();

    $this->postJson('/api/measurements', [
        'station_id'           => $station['id'],
        'temperature'          => 25.0,
        'humidity'             => 60.0,
        'atmospheric_pressure' => 1013.0,
        'reported_at'          => '2024-01-01T00:00:00Z',
    ]);

    $this->deleteJson("/api/stations/{$station['id']}")->assertStatus(409);
});