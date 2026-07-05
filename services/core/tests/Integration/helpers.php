<?php

declare(strict_types=1);

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

function coreRabbitMQTestConnection(): AMQPStreamConnection
{
    return new AMQPStreamConnection(
        host:     config('services.rabbitmq.host'),
        port:     config('services.rabbitmq.port'),
        user:     config('services.rabbitmq.user'),
        password: config('services.rabbitmq.password'),
    );
}

function purgeCoreRabbitMQQueue(string $queue): void
{
    $connection = coreRabbitMQTestConnection();
    $channel    = $connection->channel();

    try {
        $channel->queue_declare($queue, false, true, false, false);
        $channel->queue_purge($queue);
    } finally {
        $channel->close();
        $connection->close();
    }
}

/**
 * @param array<string, mixed> $headers
 */
function publishCoreMessageToQueue(string $queue, array $payload, array $headers = []): void
{
    $connection = coreRabbitMQTestConnection();
    $channel    = $connection->channel();

    try {
        $channel->queue_declare($queue, false, true, false, false);

        $properties = [
            'content_type'  => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ];

        if ($headers !== []) {
            $properties['application_headers'] = new AMQPTable($headers);
        }

        $channel->basic_publish(
            new AMQPMessage(json_encode($payload), $properties),
            '',
            $queue,
        );
    } finally {
        $channel->close();
        $connection->close();
    }
}

/** @return array<string, mixed>|null */
function peekCoreMessageHeadersFromQueue(string $queue): ?array
{
    $connection = coreRabbitMQTestConnection();
    $channel    = $connection->channel();

    try {
        $channel->queue_declare($queue, false, true, false, false);
        $message = $channel->basic_get($queue);

        if ($message === null) {
            return null;
        }

        $headers = $message->get('application_headers');

        return $headers instanceof AMQPTable ? $headers->getNativeData() : [];
    } finally {
        $channel->close();
        $connection->close();
    }
}
