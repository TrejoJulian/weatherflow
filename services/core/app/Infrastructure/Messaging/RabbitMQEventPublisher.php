<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging;

use App\Application\Contracts\EventPublisher;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

final class RabbitMQEventPublisher implements EventPublisher
{
    public function publish(string $queue, array $payload): void
    {
        $connection = new AMQPStreamConnection(
            host:     config('services.rabbitmq.host'),
            port:     config('services.rabbitmq.port'),
            user:     config('services.rabbitmq.user'),
            password: config('services.rabbitmq.password'),
        );

        $channel = $connection->channel();

        $channel->queue_declare(
            queue:       $queue,
            passive:     false,
            durable:     true,
            exclusive:   false,
            auto_delete: false,
        );

        $channel->basic_publish(
            msg: new AMQPMessage(
                body:       json_encode($payload),
                properties: [
                    'content_type'  => 'application/json',
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                ],
            ),
            exchange:    '',
            routing_key: $queue,
        );

        $channel->close();
        $connection->close();
    }
}
