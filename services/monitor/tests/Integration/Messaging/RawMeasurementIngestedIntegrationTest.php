<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\MongoDB\MeasurementModel;

// Requires monitor-worker-raw running with compose.test.yml overrides
// (QUEUE_RAW_MEASUREMENTS=raw-measurements-test, MONGODB_DATABASE=weatherflow_monitor_test).

test('persists a measurement when a RawMeasurementIngested event is published to RabbitMQ', function () {
    $stationId = '00000000-0000-4000-a000-000000000010';
    $payload   = rawMeasurementPayload(['station_id' => $stationId]);
    $queue     = config('services.queues.raw_measurements');

    publishMessageToQueue($queue, $payload);

    $measurement = waitForMeasurement($stationId);

    expect($measurement->station_name)->toBe('Universidad Nacional de Quilmes')
        ->and($measurement->temperature)->toBe(21.4)
        ->and($measurement->humidity)->toBe(70.0)
        ->and($measurement->atmospheric_pressure)->toBe(1012.0)
        ->and($measurement->reported_at)->toBe('2026-06-08T15:00:00+00:00')
        ->and($measurement->alert_status)->toBeFalse();
});

test('publishes AlertDetected when a raw measurement exceeds alert thresholds', function () {
    $stationId = '00000000-0000-4000-a000-000000000011';
    $payload   = rawMeasurementPayload([
        'station_id'   => $stationId,
        'station_name' => 'Bariloche',
        'temperature'  => 45.0,
    ]);
    $rawQueue   = config('services.queues.raw_measurements');
    $alertsQueue = config('services.queues.alerts');

    purgeRabbitMQQueue($alertsQueue);
    publishMessageToQueue($rawQueue, $payload);

    $measurement = waitForMeasurement($stationId);

    expect($measurement->alert_status)->toBeTrue()
        ->and($measurement->alert_types)->toContain('extreme_heat');

    $deadline = time() + 10;
    $alertEvent = null;

    while (time() < $deadline) {
        $alertEvent = consumeOneMessageFromQueue($alertsQueue);

        if ($alertEvent !== null) {
            break;
        }

        usleep(200_000);
    }

    expect($alertEvent)->not->toBeNull('Timed out waiting for AlertDetected on alert-events-test')
        ->and($alertEvent['event'])->toBe('AlertDetected')
        ->and($alertEvent['station_id'])->toBe($stationId)
        ->and($alertEvent['station_name'])->toBe('Bariloche')
        ->and($alertEvent['alert_types'])->toContain('extreme_heat');
});
