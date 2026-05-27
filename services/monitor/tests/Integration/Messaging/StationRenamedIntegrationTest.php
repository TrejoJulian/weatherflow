<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\MongoDB\MeasurementModel;

test('updates station_name in measurements when station is renamed via RabbitMQ', function () {
    $userId         = createUserInCore();
    $stationId      = createStationInCore($userId, 'Nombre Original');
    $otherUser      = createUserInCore();
    $otherStationId = createStationInCore($otherUser, 'Otra Estación');

    $measurementPayload = [
        'temperature'          => 20.0,
        'humidity'             => 50.0,
        'atmospheric_pressure' => 1013.0,
        'reported_at'          => '2026-05-01T12:00:00Z',
    ];

    $this->postJson('/api/measurements', array_merge($measurementPayload, [
        'station_id' => $stationId,
    ]))->assertStatus(201);

    $this->postJson('/api/measurements', array_merge($measurementPayload, [
        'station_id' => $otherStationId,
    ]))->assertStatus(201);

    expect(MeasurementModel::where('station_id', $stationId)->first()->station_name)
        ->toBe('Nombre Original');
    expect(MeasurementModel::where('station_id', $otherStationId)->first()->station_name)
        ->toBe('Otra Estación');

    renameStationInCore($stationId, $userId, 'Nombre Nuevo');

    waitForStationName($stationId, 'Nombre Nuevo', timeoutSeconds: 10);

    expect(MeasurementModel::where('station_id', $stationId)->first()->station_name)
        ->toBe('Nombre Nuevo');
    expect(MeasurementModel::where('station_id', $otherStationId)->first()->station_name)
        ->toBe('Otra Estación');
});
