<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Tests\Feature\RefreshMongoCollections;

uses(RefreshMongoCollections::class);

function fakeCoreStation(string $stationId, string $name = 'Central Buenos Aires'): void
{
    Http::fake([
        "http://core/api/stations/{$stationId}" => Http::response([
            'id'          => $stationId,
            'ownerId'     => '00000000-0000-4000-a000-000000000002',
            'stationName' => $name,
            'latitude'    => -34.6,
            'longitude'   => -58.4,
            'sensorModel' => 'Davis',
            'status'      => 'active',
        ], 200),
    ]);
}

beforeEach(function () {
    $this->collectionsToClean = ['measurements'];
    $this->cleanCollections();
    fakeCoreStation('00000000-0000-4000-a000-000000000001');
});

function measurementPayload(array $overrides = []): array
{
    return array_merge([
        'station_id'           => '00000000-0000-4000-a000-000000000001',
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
    $this->postJson('/api/measurements', measurementPayload())
        ->assertStatus(201)
        ->assertJsonStructure(['id', 'stationId', 'stationName', 'temperature', 'humidity', 'atmosphericPressure', 'reportedAt', 'alertStatus', 'alertTypes'])
        ->assertJsonFragment([
            'alertStatus' => false,
            'alertTypes'  => ['None'],
            'stationName' => 'Central Buenos Aires',
        ]);
});

test('returns 404 when station does not exist in Core', function () {
    $unknownStationId = '00000000-0000-4000-a000-000000000099';

    Http::fake([
        "http://core/api/stations/{$unknownStationId}" => Http::response(['message' => 'Station not found.'], 404),
    ]);

    $this->postJson('/api/measurements', measurementPayload(['station_id' => $unknownStationId]))
        ->assertStatus(404)
        ->assertJsonFragment(['message' => "Weather station not found: '{$unknownStationId}'"]);
});

test('returns 422 when required fields are missing', function () {
    $this->postJson('/api/measurements', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['station_id', 'temperature', 'humidity', 'atmospheric_pressure', 'reported_at']);
});

test('returns 422 when atmospheric pressure is zero or negative', function () {
    $this->postJson('/api/measurements', measurementPayload(['atmospheric_pressure' => 0.0]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['atmospheric_pressure']);
});

test('returns 422 when humidity is out of range', function () {
    $this->postJson('/api/measurements', measurementPayload(['humidity' => 150.0]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['humidity']);
});

test('creates measurement with extreme heat alert', function () {
    $this->postJson('/api/measurements', measurementPayload(['temperature' => 41.0]))
        ->assertStatus(201)
        ->assertJsonFragment([
            'alertStatus' => true,
            'alertTypes'  => ['Extreme Heat'],
        ]);
});

test('creates measurement with frost alert', function () {
    $this->postJson('/api/measurements', measurementPayload(['temperature' => -1.0]))
        ->assertStatus(201)
        ->assertJsonFragment([
            'alertStatus' => true,
            'alertTypes'  => ['Frost'],
        ]);
});

test('creates measurement with storm alert', function () {
    $this->postJson('/api/measurements', measurementPayload(['atmospheric_pressure' => 979.0]))
        ->assertStatus(201)
        ->assertJsonFragment([
            'alertStatus' => true,
            'alertTypes'  => ['Storm'],
        ]);
});

test('creates measurement with critical humidity alert', function () {
    $this->postJson('/api/measurements', measurementPayload(['humidity' => 91.0]))
        ->assertStatus(201)
        ->assertJsonFragment([
            'alertStatus' => true,
            'alertTypes'  => ['Critical Humidity'],
        ]);
});

test('creates measurement with multiple simultaneous alerts', function () {
    $response = $this->postJson('/api/measurements', measurementPayload([
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
    $created = $this->postJson('/api/measurements', measurementPayload())->json();

    $this->getJson("/api/measurements/{$created['id']}")
        ->assertStatus(200)
        ->assertJsonFragment([
            'id'          => $created['id'],
            'stationName' => 'Central Buenos Aires',
        ]);
});

test('returns 404 when measurement does not exist', function () {
    $this->getJson('/api/measurements/00000000-0000-4000-a000-000000000000')
        ->assertStatus(404);
});

// -------------------------------------------------------------------------
// GET /api/measurements
// -------------------------------------------------------------------------

test('returns all measurements when no filters are provided', function () {
    $this->postJson('/api/measurements', measurementPayload());
    $this->postJson('/api/measurements', measurementPayload(['temperature' => 30.0]));

    $this->getJson('/api/measurements')
        ->assertStatus(200)
        ->assertJsonCount(2);
});

test('filters measurements by minimum temperature', function () {
    $this->postJson('/api/measurements', measurementPayload(['temperature' => 10.0]));
    $this->postJson('/api/measurements', measurementPayload(['temperature' => 30.0]));

    $this->getJson('/api/measurements?temp_min=20')
        ->assertStatus(200)
        ->assertJsonCount(1)
        ->assertJsonFragment(['temperature' => 30.0]);
});

test('filters measurements by maximum temperature', function () {
    $this->postJson('/api/measurements', measurementPayload(['temperature' => 10.0]));
    $this->postJson('/api/measurements', measurementPayload(['temperature' => 30.0]));

    $this->getJson('/api/measurements?temp_max=20')
        ->assertStatus(200)
        ->assertJsonCount(1)
        ->assertJsonFragment(['temperature' => 10.0]);
});

test('filters only alert measurements', function () {
    $this->postJson('/api/measurements', measurementPayload(['temperature' => 20.0]));
    $this->postJson('/api/measurements', measurementPayload(['temperature' => 41.0]));

    $this->getJson('/api/measurements?alert=true')
        ->assertStatus(200)
        ->assertJsonCount(1)
        ->assertJsonFragment(['alertStatus' => true]);
});

test('filters only non-alert measurements', function () {
    $this->postJson('/api/measurements', measurementPayload(['temperature' => 20.0]));
    $this->postJson('/api/measurements', measurementPayload(['temperature' => 41.0]));

    $this->getJson('/api/measurements?alert=false')
        ->assertStatus(200)
        ->assertJsonCount(1)
        ->assertJsonFragment(['alertStatus' => false]);
});

test('filters measurements by specific alert type', function () {
    $this->postJson('/api/measurements', measurementPayload(['temperature' => 41.0]));
    $this->postJson('/api/measurements', measurementPayload(['temperature' => -1.0]));

    $this->getJson('/api/measurements?alert_type=extreme_heat')
        ->assertStatus(200)
        ->assertJsonCount(1)
        ->assertJsonFragment(['temperature' => 41.0]);
});

test('returns 422 when alert_type is invalid', function () {
    $this->getJson('/api/measurements?alert_type=invalid_type')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['alert_type']);
});

test('returns 422 when date_to is before date_from', function () {
    $this->getJson('/api/measurements?date_from=2026-04-10&date_to=2026-04-01')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['date_to']);
});

test('combines multiple filters correctly', function () {
    $this->postJson('/api/measurements', measurementPayload(['temperature' => 10.0]));
    $this->postJson('/api/measurements', measurementPayload(['temperature' => 41.0]));
    $this->postJson('/api/measurements', measurementPayload(['temperature' => 45.0]));

    $this->getJson('/api/measurements?temp_min=40&alert=true')
        ->assertStatus(200)
        ->assertJsonCount(2);
});

// -------------------------------------------------------------------------
// PUT /api/measurements/{id}
// -------------------------------------------------------------------------

test('updates a measurement and recalculates alerts', function () {
    $created = $this->postJson('/api/measurements', measurementPayload())->json();

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
    $created = $this->postJson('/api/measurements', measurementPayload())->json();

    $this->deleteJson("/api/measurements/{$created['id']}")->assertStatus(204);
    $this->getJson("/api/measurements/{$created['id']}")->assertStatus(404);
});

test('returns 404 when deleting nonexistent measurement', function () {
    $this->deleteJson('/api/measurements/00000000-0000-4000-a000-000000000000')
        ->assertStatus(404);
});
