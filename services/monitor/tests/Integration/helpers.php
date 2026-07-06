<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\MongoDB\MeasurementModel;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

function coreHttp(): PendingRequest
{
    return Http::baseUrl(config('services.core.url'))
        ->acceptJson();
}

function createUserInCore(): string
{
    $response = coreHttp()->post('/users', [
        'email'      => 'integration-' . uniqid('', true) . '@example.com',
        'first_name' => 'Integration',
        'last_name'  => 'User',
    ])->throw();

    return $response->json('id');
}

function createStationInCore(string $userId, string $name): string
{
    $response = coreHttp()->post('/stations', [
        'owner_id'     => $userId,
        'station_name' => $name,
        'latitude'     => -34.6037,
        'longitude'    => -58.3816,
        'sensor_model' => 'Davis Vantage Pro2',
    ])->throw();

    return $response->json('id');
}

/** @return array{userId: string, stationId: string} */
function createTestStation(string $name = 'Estación de Integración'): array
{
    $userId = createUserInCore();

    return [
        'userId'    => $userId,
        'stationId' => createStationInCore($userId, $name),
    ];
}

function measurementPayload(array $overrides = [], string $stationId = '00000000-0000-4000-a000-000000000001',): array
{
    return array_merge([
        'station_id'           => $stationId,
        'temperature'          => 25.0,
        'humidity'             => 60.0,
        'atmospheric_pressure' => 1013.0,
        'reported_at'          => '2026-05-01T12:00:00Z',
    ], $overrides);
}

function createMeasurementViaApi(object $test, string $stationId, array $overrides = []): array
{
    $response = $test->postJson('/api/measurements', measurementPayload($overrides, $stationId));

    $response->assertStatus(201);

    return $response->json();
}

function waitForStationName(string $stationId, string $expectedName, int $timeoutSeconds = 10): void
{
    $deadline = time() + $timeoutSeconds;

    while (time() < $deadline) {
        $model = MeasurementModel::where('station_id', $stationId)->first();

        if ($model !== null && $model->station_name === $expectedName) {
            return;
        }

        usleep(200_000);
    }

    $actual = MeasurementModel::where('station_id', $stationId)->first()?->station_name;

    expect($actual)->toBe($expectedName, "Timed out after {$timeoutSeconds}s waiting for station_name on station {$stationId}");
}

function renameStationInCore(string $stationId, string $userId, string $newName): void
{
    $station = coreHttp()
        ->get("/stations/{$stationId}")
        ->throw()
        ->json();

    coreHttp()
        ->put("/stations/{$stationId}", [
            'owner_id'     => $userId,
            'station_name' => $newName,
            'latitude'     => $station['latitude'],
            'longitude'    => $station['longitude'],
            'sensor_model' => $station['sensorModel'],
            'status'       => $station['status'],
        ])
        ->throw();
}

function purgeRabbitMQQueue(string $queue): void
{
    $connection = rabbitMQTestConnection();
    $channel    = $connection->channel();

    try {
        $channel->queue_declare(
            queue:       $queue,
            passive:     false,
            durable:     true,
            exclusive:   false,
            auto_delete: false,
        );
        $channel->queue_purge($queue);
    } finally {
        $channel->close();
        $connection->close();
    }
}

function consumeOneMessageFromQueue(string $queue): ?array
{
    $connection = rabbitMQTestConnection();
    $channel    = $connection->channel();

    try {
        $channel->queue_declare(
            queue:       $queue,
            passive:     false,
            durable:     true,
            exclusive:   false,
            auto_delete: false,
        );

        $message = $channel->basic_get($queue);

        if ($message === null) {
            return null;
        }

        $channel->basic_ack($message->getDeliveryTag());

        return json_decode($message->getBody(), true, 512, JSON_THROW_ON_ERROR);
    } finally {
        $channel->close();
        $connection->close();
    }
}

function rabbitMQTestConnection(): AMQPStreamConnection
{
    return new AMQPStreamConnection(
        host:     config('services.rabbitmq.host'),
        port:     config('services.rabbitmq.port'),
        user:     config('services.rabbitmq.user'),
        password: config('services.rabbitmq.password'),
    );
}

function publishMessageToQueue(string $queue, array $payload, array $headers = []): void
{
    $connection = rabbitMQTestConnection();
    $channel    = $connection->channel();

    try {
        $channel->queue_declare(
            queue:       $queue,
            passive:     false,
            durable:     true,
            exclusive:   false,
            auto_delete: false,
        );

        $properties = [
            'content_type'  => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ];

        if ($headers !== []) {
            $properties['application_headers'] = new \PhpAmqpLib\Wire\AMQPTable($headers);
        }

        $channel->basic_publish(
            msg: new AMQPMessage(
                body:       json_encode($payload),
                properties: $properties,
            ),
            exchange:    '',
            routing_key: $queue,
        );
    } finally {
        $channel->close();
        $connection->close();
    }
}

/** @return array<string, mixed>|null */
function peekMessageHeadersFromQueue(string $queue): ?array
{
    $connection = rabbitMQTestConnection();
    $channel    = $connection->channel();

    try {
        $channel->queue_declare(
            queue:       $queue,
            passive:     false,
            durable:     true,
            exclusive:   false,
            auto_delete: false,
        );

        $message = $channel->basic_get($queue);

        if ($message === null) {
            return null;
        }

        $headers = $message->get('application_headers');

        return $headers instanceof \PhpAmqpLib\Wire\AMQPTable ? $headers->getNativeData() : [];
    } finally {
        $channel->close();
        $connection->close();
    }
}

function rawMeasurementPayload(array $overrides = []): array
{
    return array_merge([
        'event'                => 'RawMeasurementIngested',
        'station_id'           => '00000000-0000-4000-a000-000000000010',
        'station_name'         => 'Universidad Nacional de Quilmes',
        'provider'             => 'openweather',
        'temperature'          => 21.4,
        'humidity'             => 70.0,
        'atmospheric_pressure' => 1012.0,
        'reported_at'          => '2026-06-08T15:00:00Z',
        'trace_id'             => 'ingest-integration-test',
    ], $overrides);
}

function waitForMeasurement(string $stationId, int $timeoutSeconds = 10): MeasurementModel
{
    $deadline = time() + $timeoutSeconds;

    while (time() < $deadline) {
        $model = MeasurementModel::where('station_id', $stationId)->first();

        if ($model !== null) {
            return $model;
        }

        usleep(200_000);
    }

    expect(true)->toBeFalse("Timed out after {$timeoutSeconds}s waiting for measurement on station {$stationId}");
}
