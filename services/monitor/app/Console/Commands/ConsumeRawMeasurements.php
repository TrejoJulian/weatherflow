<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Messaging\RawMeasurementHandler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPExceptionInterface;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Throwable;

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
                callback:     function (AMQPMessage $message) use ($handler, $channel, $rawMeasurementsQueue): void {
                    $this->process($message, $handler, $channel, $rawMeasurementsQueue);
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

    /**
     * The ack decision depends on the processing outcome:
     * - success or a permanent failure (unparseable / missing fields): ack and move on;
     * - transient failure (e.g. persistence down): requeue with a bounded retry counter.
     */
    private function process(AMQPMessage $message, RawMeasurementHandler $handler, AMQPChannel $channel, string $queue): void
    {
        $payload = json_decode($message->body, true);

        if (! is_array($payload) || ($payload['event'] ?? null) !== 'RawMeasurementIngested' || $this->hasMissingFields($payload)) {
            Log::warning('Discarding malformed raw-measurement message', [
                'body' => $message->body,
            ]);
            $message->ack();

            return;
        }

        try {
            $handler->handle($payload);
            $this->info("[monitor] RawMeasurementIngested — station {$payload['station_id']} ({$payload['station_name']})");
            $message->ack();
        } catch (Throwable $exception) {
            $this->requeueOrDiscard($message, $channel, $queue, $exception);
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function hasMissingFields(array $payload): bool
    {
        $required = ['station_id', 'station_name', 'temperature', 'humidity', 'atmospheric_pressure', 'reported_at'];

        foreach ($required as $field) {
            if (! isset($payload[$field])) {
                return true;
            }
        }

        return false;
    }

    private function requeueOrDiscard(AMQPMessage $message, AMQPChannel $channel, string $queue, Throwable $exception): void
    {
        $retryCount = $this->retryCount($message);
        $maxRetries = (int) config('services.rabbitmq.max_retries');

        if ($retryCount >= $maxRetries) {
            Log::error('Discarding raw-measurement after exhausting retries', [
                'retries' => $retryCount,
                'error'   => $exception->getMessage(),
                'body'    => $message->body,
            ]);
            $message->ack();

            return;
        }

        Log::warning('Requeueing raw-measurement after transient failure', [
            'attempt' => $retryCount + 1,
            'error'   => $exception->getMessage(),
        ]);

        sleep((int) config('services.rabbitmq.retry_delay'));

        $retried = new AMQPMessage($message->body, [
            'delivery_mode'       => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'application_headers' => new AMQPTable(['x-retry-count' => $retryCount + 1]),
        ]);
        $channel->basic_publish($retried, '', $queue);
        $message->ack();
    }

    private function retryCount(AMQPMessage $message): int
    {
        $headers = $message->get_properties()['application_headers'] ?? null;

        if ($headers instanceof AMQPTable) {
            return (int) ($headers->getNativeData()['x-retry-count'] ?? 0);
        }

        return 0;
    }
}
