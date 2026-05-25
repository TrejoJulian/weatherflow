<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\MongoDB\MeasurementModel;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use PhpAmqpLib\Connection\AMQPStreamConnection;

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
