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
