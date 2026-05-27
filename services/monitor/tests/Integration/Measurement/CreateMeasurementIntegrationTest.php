<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\MongoDB\MeasurementModel;

test('registers a measurement and publishes AlertDetected to RabbitMQ', function () {
    purgeRabbitMQQueue(config('services.queues.alerts'));

    $userId    = createUserInCore();
    $stationId = createStationInCore($userId, 'Estación Central');

    $response = $this->postJson('/api/measurements', [
        'station_id'           => $stationId,
        'temperature'          => 41.5,
        'humidity'             => 65.0,
        'atmospheric_pressure' => 1013.0,
        'reported_at'          => '2026-05-01T12:00:00Z',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('stationName', 'Estación Central')
        ->assertJsonPath('alertStatus', true)
        ->assertJsonFragment(['alertTypes' => ['Extreme Heat']]);

    $model = MeasurementModel::where('station_id', $stationId)->first();
    expect($model)->not->toBeNull()
        ->and($model->station_name)->toBe('Estación Central')
        ->and($model->alert_status)->toBeTrue();

    $message = consumeOneMessageFromQueue(config('services.queues.alerts'));
    expect($message)->not->toBeNull()
        ->and($message['event'])->toBe('AlertDetected')
        ->and($message['station_id'])->toBe($stationId)
        ->and($message['station_name'])->toBe('Estación Central')
        ->and($message['alert_types'])->toContain('extreme_heat');
});

test('does not publish AlertDetected when measurement has no alert', function () {
    purgeRabbitMQQueue(config('services.queues.alerts'));

    ['stationId' => $stationId] = createTestStation('Estación Sin Alerta');

    $this->postJson('/api/measurements', measurementPayload([], $stationId))
        ->assertStatus(201)
        ->assertJsonFragment([
            'alertStatus' => false,
            'alertTypes'  => ['None'],
        ]);

    expect(consumeOneMessageFromQueue(config('services.queues.alerts')))->toBeNull();
});

test('publishes frost alert to RabbitMQ when temperature is below zero', function () {
    purgeRabbitMQQueue(config('services.queues.alerts'));

    ['stationId' => $stationId] = createTestStation('Estación Helada');

    $this->postJson('/api/measurements', measurementPayload([
        'temperature' => -2.0,
    ], $stationId))->assertStatus(201)
        ->assertJsonFragment([
            'alertStatus' => true,
            'alertTypes'  => ['Frost'],
        ]);

    $message = consumeOneMessageFromQueue(config('services.queues.alerts'));
    expect($message)->not->toBeNull()
        ->and($message['event'])->toBe('AlertDetected')
        ->and($message['station_id'])->toBe($stationId)
        ->and($message['alert_types'])->toContain('frost');
});

test('returns 404 when station does not exist in Core', function () {
    $unknownStationId = '00000000-0000-4000-a000-000000000099';

    $this->postJson('/api/measurements', [
        'station_id'           => $unknownStationId,
        'temperature'          => 20.0,
        'humidity'             => 50.0,
        'atmospheric_pressure' => 1013.0,
        'reported_at'          => '2026-05-01T12:00:00Z',
    ])->assertStatus(404);

    expect(MeasurementModel::count())->toBe(0);
});
