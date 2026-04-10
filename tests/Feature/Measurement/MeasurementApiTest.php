<?php

declare(strict_types=1);

use Tests\Feature\RefreshMongoCollections;

uses(RefreshMongoCollections::class);

beforeEach(function () {
    $this->collectionsToClean = ['measurements', 'weather_stations', 'users'];
    $this->cleanCollections();
});

function createOwner($test): string
{
    return $test->postJson('/api/users', [
        'email'      => 'owner@example.com',
        'first_name' => 'Owner',
        'last_name'  => 'User',
    ])->json('id');
}

function createStation($test, string $ownerId): string
{
    return $test->postJson('/api/stations', [
        'owner_id'     => $ownerId,
        'station_name' => 'Estación Central',
        'latitude'     => -34.6037,
        'longitude'    => -58.3816,
        'sensor_model' => 'Davis Vantage Pro2',
    ])->json('id');
}

function measurementPayload(string $stationId, array $overrides = []): array
{
    return array_merge([
        'station_id'           => $stationId,
        'temperature'          => 25.0,
        'humidity'             => 60.0,
        'atmospheric_pressure' => 1013.0,
        'reported_at'          => '2026-04-06T14:30:00Z',
    ], $overrides);
}

// -------------------------------------------------------------------------
// POST /api/measurements
// -------------------------------------------------------------------------

test('creates a measurement and returns 201', function () {
    $stationId = createStation($this, createOwner($this));

    $this->postJson('/api/measurements', measurementPayload($stationId))
        ->assertStatus(201)
        ->assertJsonStructure(['id', 'stationId', 'temperature', 'humidity', 'atmosphericPressure', 'reportedAt', 'alertStatus', 'alertTypes'])
        ->assertJsonFragment([
            'alertStatus' => false,
            'alertTypes'  => ['None'],
        ]);
});

test('returns 422 when station does not exist', function () {
    $this->postJson('/api/measurements', measurementPayload('00000000-0000-4000-a000-000000000000'))
        ->assertStatus(422);
});

test('returns 422 when required fields are missing', function () {
    $this->postJson('/api/measurements', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['station_id', 'temperature', 'humidity', 'atmospheric_pressure', 'reported_at']);
});

test('returns 422 when humidity is out of range', function () {
    $stationId = createStation($this, createOwner($this));

    $this->postJson('/api/measurements', measurementPayload($stationId, ['humidity' => 150.0]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['humidity']);
});

test('creates measurement with extreme heat alert', function () {
    $stationId = createStation($this, createOwner($this));

    $this->postJson('/api/measurements', measurementPayload($stationId, ['temperature' => 41.0]))
        ->assertStatus(201)
        ->assertJsonFragment([
            'alertStatus' => true,
            'alertTypes'  => ['Extreme Heat'],
        ]);
});

test('creates measurement with frost alert', function () {
    $stationId = createStation($this, createOwner($this));

    $this->postJson('/api/measurements', measurementPayload($stationId, ['temperature' => -1.0]))
        ->assertStatus(201)
        ->assertJsonFragment([
            'alertStatus' => true,
            'alertTypes'  => ['Frost'],
        ]);
});

test('creates measurement with storm alert', function () {
    $stationId = createStation($this, createOwner($this));

    $this->postJson('/api/measurements', measurementPayload($stationId, ['atmospheric_pressure' => 979.0]))
        ->assertStatus(201)
        ->assertJsonFragment([
            'alertStatus' => true,
            'alertTypes'  => ['Storm'],
        ]);
});

test('creates measurement with critical humidity alert', function () {
    $stationId = createStation($this, createOwner($this));

    $this->postJson('/api/measurements', measurementPayload($stationId, ['humidity' => 91.0]))
        ->assertStatus(201)
        ->assertJsonFragment([
            'alertStatus' => true,
            'alertTypes'  => ['Critical Humidity'],
        ]);
});

test('creates measurement with multiple simultaneous alerts', function () {
    $stationId = createStation($this, createOwner($this));

    $response = $this->postJson('/api/measurements', measurementPayload($stationId, [
        'temperature' => -5.0,
        'humidity'    => 95.0,
    ]))->assertStatus(201);

    expect($response->json('alertStatus'))->toBeTrue()
        ->and($response->json('alertTypes'))->toContain('Frost')
        ->and($response->json('alertTypes'))->toContain('Critical Humidity');
});

// -------------------------------------------------------------------------
// GET /api/measurements/{id}
// -------------------------------------------------------------------------

test('returns a measurement by id', function () {
    $stationId = createStation($this, createOwner($this));

    $created = $this->postJson('/api/measurements', measurementPayload($stationId))->json();

    $this->getJson("/api/measurements/{$created['id']}")
        ->assertStatus(200)
        ->assertJsonFragment(['id' => $created['id']]);
});

test('returns 404 when measurement does not exist', function () {
    $this->getJson('/api/measurements/00000000-0000-4000-a000-000000000000')
        ->assertStatus(404);
});

// -------------------------------------------------------------------------
// GET /api/measurements
// -------------------------------------------------------------------------

test('returns all measurements', function () {
    $stationId = createStation($this, createOwner($this));

    $this->postJson('/api/measurements', measurementPayload($stationId));
    $this->postJson('/api/measurements', measurementPayload($stationId, ['temperature' => 30.0]));

    $this->getJson('/api/measurements')
        ->assertStatus(200)
        ->assertJsonCount(2);
});

// -------------------------------------------------------------------------
// PUT /api/measurements/{id}
// -------------------------------------------------------------------------

test('updates a measurement and recalculates alerts', function () {
    $stationId = createStation($this, createOwner($this));

    $created = $this->postJson('/api/measurements', measurementPayload($stationId))->json();

    $this->putJson("/api/measurements/{$created['id']}", [
        'temperature'          => 41.0,
        'humidity'             => 50.0,
        'atmospheric_pressure' => 1013.0,
        'reported_at'          => '2026-04-07T10:00:00Z',
    ])->assertStatus(200)
        ->assertJsonFragment([
            'alertStatus' => true,
            'alertTypes'  => ['Extreme Heat'],
        ]);
});

test('returns 404 when updating nonexistent measurement', function () {
    $this->putJson('/api/measurements/00000000-0000-4000-a000-000000000000', [
        'temperature'          => 20.0,
        'humidity'             => 50.0,
        'atmospheric_pressure' => 1013.0,
        'reported_at'          => '2026-04-07T10:00:00Z',
    ])->assertStatus(404);
});

// -------------------------------------------------------------------------
// DELETE /api/measurements/{id}
// -------------------------------------------------------------------------

test('deletes a measurement and returns 204', function () {
    $stationId = createStation($this, createOwner($this));

    $created = $this->postJson('/api/measurements', measurementPayload($stationId))->json();

    $this->deleteJson("/api/measurements/{$created['id']}")->assertStatus(204);
    $this->getJson("/api/measurements/{$created['id']}")->assertStatus(404);
});

test('returns 404 when deleting nonexistent measurement', function () {
    $this->deleteJson('/api/measurements/00000000-0000-4000-a000-000000000000')
        ->assertStatus(404);
});