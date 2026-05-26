<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Messaging\StationRenamedHandler;
use App\Domain\Measurement\Repositories\MeasurementRepository;
use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

final class ConsumeStationEvents extends Command
{
    protected $signature   = 'monitor:consume-station-events';
    protected $description = 'Consume station-events from RabbitMQ and sync measurement data';

    public function handle(MeasurementRepository $measurementRepository): void
    {
        $handler = new StationRenamedHandler($measurementRepository);

        $connection = new AMQPStreamConnection(
            host:     config('services.rabbitmq.host'),
            port:     config('services.rabbitmq.port'),
            user:     config('services.rabbitmq.user'),
            password: config('services.rabbitmq.password'),
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
    }
}
