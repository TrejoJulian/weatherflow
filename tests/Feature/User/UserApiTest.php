<?php

declare(strict_types=1);

use Tests\Feature\RefreshMongoCollections;

uses(RefreshMongoCollections::class);

beforeEach(function () {
    $this->collectionsToClean = ['users', 'weather_stations'];
    $this->cleanCollections();
});

// -------------------------------------------------------------------------
// POST /api/users
// -------------------------------------------------------------------------

test('creates a user and returns 201', function () {
    $response = $this->postJson('/api/users', [
        'email'      => 'john@example.com',
        'first_name' => 'John',
        'last_name'  => 'Doe',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['id', 'email', 'firstName', 'lastName'])
        ->assertJsonFragment([
            'email'     => 'john@example.com',
            'firstName' => 'John',
            'lastName'  => 'Doe',
        ]);
});

test('returns 409 when email already exists', function () {
    $this->postJson('/api/users', [
        'email'      => 'john@example.com',
        'first_name' => 'John',
        'last_name'  => 'Doe',
    ]);

    $this->postJson('/api/users', [
        'email'      => 'john@example.com',
        'first_name' => 'Jane',
        'last_name'  => 'Smith',
    ])->assertStatus(409);
});

test('returns 422 when required fields are missing', function () {
    $this->postJson('/api/users', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'first_name', 'last_name']);
});

test('returns 422 when email format is invalid', function () {
    $this->postJson('/api/users', [
        'email'      => 'not-an-email',
        'first_name' => 'John',
        'last_name'  => 'Doe',
    ])->assertStatus(422)->assertJsonValidationErrors(['email']);
});

// -------------------------------------------------------------------------
// GET /api/users/{id}
// -------------------------------------------------------------------------

test('returns a user by id', function () {
    $created = $this->postJson('/api/users', [
        'email'      => 'john@example.com',
        'first_name' => 'John',
        'last_name'  => 'Doe',
    ])->json();

    $this->getJson("/api/users/{$created['id']}")
        ->assertStatus(200)
        ->assertJsonFragment(['email' => 'john@example.com']);
});

test('returns 404 when user does not exist', function () {
    $this->getJson('/api/users/00000000-0000-4000-a000-000000000000')
        ->assertStatus(404);
});

// -------------------------------------------------------------------------
// GET /api/users
// -------------------------------------------------------------------------

test('returns all users', function () {
    $this->postJson('/api/users', ['email' => 'a@example.com', 'first_name' => 'A', 'last_name' => 'A']);
    $this->postJson('/api/users', ['email' => 'b@example.com', 'first_name' => 'B', 'last_name' => 'B']);

    $this->getJson('/api/users')
        ->assertStatus(200)
        ->assertJsonCount(2);
});

// -------------------------------------------------------------------------
// PUT /api/users/{id}
// -------------------------------------------------------------------------

test('updates a user', function () {
    $created = $this->postJson('/api/users', [
        'email'      => 'john@example.com',
        'first_name' => 'John',
        'last_name'  => 'Doe',
    ])->json();

    $this->putJson("/api/users/{$created['id']}", [
        'email'      => 'updated@example.com',
        'first_name' => 'Johnny',
        'last_name'  => 'Updated',
    ])->assertStatus(200)->assertJsonFragment([
        'email'     => 'updated@example.com',
        'firstName' => 'Johnny',
    ]);
});

test('returns 409 when updating to an email already in use', function () {
    $this->postJson('/api/users', ['email' => 'taken@example.com', 'first_name' => 'A', 'last_name' => 'A']);

    $second = $this->postJson('/api/users', [
        'email'      => 'john@example.com',
        'first_name' => 'John',
        'last_name'  => 'Doe',
    ])->json();

    $this->putJson("/api/users/{$second['id']}", [
        'email'      => 'taken@example.com',
        'first_name' => 'John',
        'last_name'  => 'Doe',
    ])->assertStatus(409);
});

test('returns 404 when updating nonexistent user', function () {
    $this->putJson('/api/users/00000000-0000-4000-a000-000000000000', [
        'email'      => 'john@example.com',
        'first_name' => 'John',
        'last_name'  => 'Doe',
    ])->assertStatus(404);
});

// -------------------------------------------------------------------------
// DELETE /api/users/{id}
// -------------------------------------------------------------------------

test('deletes a user and returns 204', function () {
    $created = $this->postJson('/api/users', [
        'email'      => 'john@example.com',
        'first_name' => 'John',
        'last_name'  => 'Doe',
    ])->json();

    $this->deleteJson("/api/users/{$created['id']}")->assertStatus(204);
    $this->getJson("/api/users/{$created['id']}")->assertStatus(404);
});

test('returns 404 when deleting nonexistent user', function () {
    $this->deleteJson('/api/users/00000000-0000-4000-a000-000000000000')
        ->assertStatus(404);
});

// -------------------------------------------------------------------------
// POST /api/users/{id}/subscriptions
// -------------------------------------------------------------------------

test('subscribes a user to a station and returns station details', function () {
    $user = $this->postJson('/api/users', [
        'email'      => 'john@example.com',
        'first_name' => 'John',
        'last_name'  => 'Doe',
    ])->json();

    $station = $this->postJson('/api/stations', [
        'owner_id'     => $user['id'],
        'station_name' => 'Station Alpha',
        'latitude'     => -34.6,
        'longitude'    => -58.3,
        'sensor_model' => 'Davis',
    ])->json();

    $this->postJson("/api/users/{$user['id']}/subscriptions", [
        'station_id' => $station['id'],
    ])->assertStatus(200)
        ->assertJsonStructure(['id', 'email', 'firstName', 'lastName', 'subscriptions'])
        ->assertJsonFragment([
            'stationId' => $station['id'],
            'name'      => 'Station Alpha',
        ]);
});

test('returns 409 when subscribing to a station already subscribed', function () {
    $user = $this->postJson('/api/users', [
        'email'      => 'john@example.com',
        'first_name' => 'John',
        'last_name'  => 'Doe',
    ])->json();

    $station = $this->postJson('/api/stations', [
        'owner_id'     => $user['id'],
        'station_name' => 'Station Alpha',
        'latitude'     => -34.6,
        'longitude'    => -58.3,
        'sensor_model' => 'Davis',
    ])->json();

    $this->postJson("/api/users/{$user['id']}/subscriptions", ['station_id' => $station['id']]);
    $this->postJson("/api/users/{$user['id']}/subscriptions", ['station_id' => $station['id']])
        ->assertStatus(409);
});

test('returns 404 when subscribing to a nonexistent station', function () {
    $user = $this->postJson('/api/users', [
        'email'      => 'john@example.com',
        'first_name' => 'John',
        'last_name'  => 'Doe',
    ])->json();

    $this->postJson("/api/users/{$user['id']}/subscriptions", [
        'station_id' => '00000000-0000-4000-a000-000000000000',
    ])->assertStatus(404);
});

test('returns 404 when subscribing a nonexistent user', function () {
    $this->postJson('/api/users/00000000-0000-4000-a000-000000000000/subscriptions', [
        'station_id' => '00000000-0000-4000-a000-000000000001',
    ])->assertStatus(404);
});

// -------------------------------------------------------------------------
// DELETE /api/users/{id}/subscriptions/{stationId}
// -------------------------------------------------------------------------

test('unsubscribes a user from a station', function () {
    $user = $this->postJson('/api/users', [
        'email'      => 'john@example.com',
        'first_name' => 'John',
        'last_name'  => 'Doe',
    ])->json();

    $station = $this->postJson('/api/stations', [
        'owner_id'     => $user['id'],
        'station_name' => 'Station Alpha',
        'latitude'     => -34.6,
        'longitude'    => -58.3,
        'sensor_model' => 'Davis',
    ])->json();

    $this->postJson("/api/users/{$user['id']}/subscriptions", ['station_id' => $station['id']]);

    $this->deleteJson("/api/users/{$user['id']}/subscriptions/{$station['id']}")
        ->assertStatus(204);

    $this->getJson("/api/users/{$user['id']}")
        ->assertJsonFragment(['subscriptions' => []]);
});

test('unsubscribe returns 204 even when not subscribed', function () {
    $user = $this->postJson('/api/users', [
        'email'      => 'john@example.com',
        'first_name' => 'John',
        'last_name'  => 'Doe',
    ])->json();

    $this->deleteJson("/api/users/{$user['id']}/subscriptions/00000000-0000-4000-a000-000000000000")
        ->assertStatus(204);
});