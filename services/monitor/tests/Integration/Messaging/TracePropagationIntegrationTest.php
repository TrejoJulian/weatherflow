<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\MongoDB\MeasurementModel;

// Requires monitor-worker-raw running with compose.test.yml overrides
// (QUEUE_RAW_MEASUREMENTS=raw-measurements-test, MONGODB_DATABASE=weatherflow_monitor_test).

test('propagates trace_id from traceparent header to AlertDetected payload', function () {
    $stationId   = '00000000-0000-4000-a000-000000000012';
    $traceId     = '4bf92f3577b34da6a3ce929d0e0e4736';
    $traceparent = '00-'.$traceId.'-00f067aa0ba902b7-01';
    $payload     = rawMeasurementPayload([
        'station_id'   => $stationId,
        'station_name' => 'Ushuaia',
        'temperature'  => 45.0,
        'trace_id'     => $traceId,
    ]);
    $rawQueue    = config('services.queues.raw_measurements');
    $alertsQueue = config('services.queues.alerts');

    purgeRabbitMQQueue($alertsQueue);
    publishMessageToQueue($rawQueue, $payload, ['traceparent' => $traceparent]);

    $measurement = waitForMeasurement($stationId);

    expect($measurement->alert_status)->toBeTrue();

    $deadline   = time() + 10;
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
        ->and($alertEvent['trace_id'])->toBe($traceId);
});
