<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Messaging\RawMeasurementHandler;
use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

final class ConsumeRawMeasurements extends Command
{
    protected $signature   = 'monitor:consume-raw-measurements';
    protected $description = 'Consume raw-measurements from RabbitMQ and persist ingested readings';

    public function handle(RawMeasurementHandler $handler): void
    {
        $connection = new AMQPStreamConnection(
            host:     config('services.rabbitmq.host'),
            port:     config('services.rabbitmq.port'),
            user:     config('services.rabbitmq.user'),
            password: config('services.rabbitmq.password'),
        );

        $channel = $connection->channel();
        $rawMeasurementsQueue = config('services.queues.raw_measurements');
        $channel->queue_declare($rawMeasurementsQueue, false, true, false, false);

        $this->info("[monitor] Listening on {$rawMeasurementsQueue}...");

        $channel->basic_consume(
            queue:        $rawMeasurementsQueue,
            consumer_tag: '',
            no_local:     false,
            no_ack:       false,
            exclusive:    false,
            nowait:       false,
            callback:     function (AMQPMessage $message) use ($handler): void {
                $payload = json_decode($message->body, true);

                if (($payload['event'] ?? null) === 'RawMeasurementIngested') {
                    $handler->handle($payload);
                    $this->info("[monitor] RawMeasurementIngested — station {$payload['station_id']} ({$payload['station_name']})");
                }

                $message->ack();
            },
        );

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }
}
