<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Messaging\StationRenamedHandler;
use App\Domain\Measurement\Repositories\MeasurementRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPExceptionInterface;
use PhpAmqpLib\Message\AMQPMessage;

final class ConsumeStationEvents extends Command
{
    protected $signature   = 'monitor:consume-station-events';
    protected $description = 'Consume station-events from RabbitMQ and sync measurement data';

    public function handle(MeasurementRepository $measurementRepository): int
    {
        $handler = new StationRenamedHandler($measurementRepository);

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
            $stationsQueue = config('services.queues.stations');
            $channel->queue_declare($stationsQueue, false, true, false, false);

            $this->info("[monitor] Listening on {$stationsQueue}...");

            $channel->basic_consume(
                queue:        $stationsQueue,
                consumer_tag: '',
                no_local:     false,
                no_ack:       false,
                exclusive:    false,
                nowait:       false,
                callback:     function (AMQPMessage $message) use ($handler): void {
                    $payload = json_decode($message->body, true);

                    if (($payload['event'] ?? null) === 'StationRenamed') {
                        $handler->handle($payload);
                        $this->info("[monitor] StationRenamed — station {$payload['station_id']} renamed to '{$payload['new_name']}'");
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
            Log::error('Station events consumer lost connection to RabbitMQ', [
                'error' => $exception->getMessage(),
            ]);
            $this->error("[monitor] RabbitMQ connection failed: {$exception->getMessage()}");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
