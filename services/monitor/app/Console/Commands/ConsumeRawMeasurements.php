<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Messaging\RawMeasurementHandler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPExceptionInterface;
use PhpAmqpLib\Message\AMQPMessage;

final class ConsumeRawMeasurements extends Command
{
    protected $signature   = 'monitor:consume-raw-measurements';
    protected $description = 'Consume raw-measurements from RabbitMQ and persist ingested readings';

    public function handle(RawMeasurementHandler $handler): int
    {
        try {
            $connection = new AMQPStreamConnection(
                host:               config('services.rabbitmq.host'),
                port:               config('services.rabbitmq.port'),
                user:               config('services.rabbitmq.user'),
                password:           config('services.rabbitmq.password'),
                connection_timeout: config('services.rabbitmq.connection_timeout'),
                read_write_timeout: config('services.rabbitmq.consumer_read_write_timeout'),
                heartbeat:          config('services.rabbitmq.heartbeat'),
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
        } catch (AMQPExceptionInterface $exception) {
            // Exit non-zero so the container restarts and reconnects to the broker.
            Log::error('Raw measurements consumer lost connection to RabbitMQ', [
                'error' => $exception->getMessage(),
            ]);
            $this->error("[monitor] RabbitMQ connection failed: {$exception->getMessage()}");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
