<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\MongoDB\MeasurementModel;

test('retrieves a measurement by id with station name resolved from Core', function () {
    ['stationId' => $stationId] = createTestStation('Estación Norte');

    $created = createMeasurementViaApi($this, $stationId);

    $this->getJson("/api/measurements/{$created['id']}")
        ->assertStatus(200)
        ->assertJsonFragment([
            'id'          => $created['id'],
            'stationId'   => $stationId,
            'stationName' => 'Estación Norte',
            'alertStatus' => false,
        ]);
});

test('lists and filters measurements persisted in MongoDB', function () {
    ['stationId' => $stationA] = createTestStation('Estación A');
    ['stationId' => $stationB] = createTestStation('Estación B');

    createMeasurementViaApi($this, $stationA, ['temperature' => 20.0]);
    createMeasurementViaApi($this, $stationA, ['temperature' => 41.0]);
    createMeasurementViaApi($this, $stationB, ['temperature' => 15.0]);

    $this->getJson('/api/measurements')
        ->assertStatus(200)
        ->assertJsonCount(3);

    $this->getJson('/api/measurements?temp_min=40&alert=true')
        ->assertStatus(200)
        ->assertJsonCount(1)
        ->assertJsonFragment([
            'stationName' => 'Estación A',
            'temperature' => 41.0,
            'alertStatus' => true,
        ]);

    $this->getJson('/api/measurements?station_name=Estación B')
        ->assertStatus(200)
        ->assertJsonCount(1)
        ->assertJsonFragment([
            'stationName' => 'Estación B',
            'temperature' => 15.0,
        ]);

    expect(MeasurementModel::count())->toBe(3);
});

test('updates a measurement and persists recalculated alerts in MongoDB', function () {
    ['stationId' => $stationId] = createTestStation('Estación Sur');

    $created = createMeasurementViaApi($this, $stationId, ['temperature' => 22.0]);

    $this->putJson("/api/measurements/{$created['id']}", [
        'temperature'          => 41.0,
        'humidity'             => 50.0,
        'atmospheric_pressure' => 1013.0,
        'reported_at'          => '2026-05-02T08:00:00Z',
    ])->assertStatus(200)
        ->assertJsonFragment([
            'alertStatus' => true,
            'alertTypes'  => ['Extreme Heat'],
        ]);

    $model = MeasurementModel::find($created['id']);
    expect($model)->not->toBeNull()
        ->and($model->temperature)->toBe(41.0)
        ->and($model->alert_status)->toBeTrue()
        ->and($model->alert_types)->toContain('extreme_heat');
});

test('deletes a measurement from MongoDB', function () {
    ['stationId' => $stationId] = createTestStation('Estación Oeste');

    $created = createMeasurementViaApi($this, $stationId);

    $this->deleteJson("/api/measurements/{$created['id']}")->assertStatus(204);
    $this->getJson("/api/measurements/{$created['id']}")->assertStatus(404);

    expect(MeasurementModel::find($created['id']))->toBeNull();
});
