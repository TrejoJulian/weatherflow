<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Messaging\RawMeasurementHandler;
use App\Infrastructure\Observability\TraceContextCarrier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\TracerInterface;
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

    public function handle(RawMeasurementHandler $handler, TracerInterface $tracer): int
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
                callback:     function (AMQPMessage $message) use ($handler, $channel, $rawMeasurementsQueue, $tracer): void {
                    $this->process($message, $handler, $channel, $rawMeasurementsQueue, $tracer);
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
    private function process(AMQPMessage $message, RawMeasurementHandler $handler, AMQPChannel $channel, string $queue, TracerInterface $tracer): void
    {
        $payload = json_decode($message->body, true);

        if (! is_array($payload) || ($payload['event'] ?? null) !== 'RawMeasurementIngested' || $this->hasMissingFields($payload)) {
            Log::warning('Discarding malformed raw-measurement message', [
                'message_outcome' => 'discarded_malformed',
                'body'            => $message->body,
            ]);
            $message->ack();

            return;
        }

        $headers = $message->get_properties()['application_headers'] ?? null;
        $parentContext = TraceContextCarrier::extractFromAmqpHeaders(
            $headers instanceof AMQPTable ? $headers : null,
        );
        $parentScope = $parentContext->activate();

        $span = $tracer->spanBuilder('consume')
            ->setSpanKind(SpanKind::KIND_CONSUMER)
            ->setAttribute('station_id', $payload['station_id'])
            ->startSpan();
        $spanScope = $span->activate();

        try {
            $handler->handle($payload);
            Log::info('Raw measurement processed', [
                'message_outcome' => 'processed',
                'trace_id'        => $payload['trace_id'] ?? null,
                'station_id'      => $payload['station_id'],
            ]);
            $message->ack();
        } catch (Throwable $exception) {
            $this->requeueOrDiscard($message, $channel, $queue, $exception, $payload);
        } finally {
            $spanScope->detach();
            $span->end();
            $parentScope->detach();
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

    /**
     * @param array<string, mixed> $payload
     */
    private function requeueOrDiscard(AMQPMessage $message, AMQPChannel $channel, string $queue, Throwable $exception, array $payload): void
    {
        $retryCount = $this->retryCount($message);
        $maxRetries = (int) config('services.rabbitmq.max_retries');

        if ($retryCount >= $maxRetries) {
            Log::error('Discarding raw-measurement after exhausting retries', [
                'message_outcome' => 'discarded_retries_exhausted',
                'retry_count'     => $retryCount,
                'trace_id'        => $payload['trace_id'] ?? null,
                'error'           => $exception->getMessage(),
                'body'            => $message->body,
            ]);
            $message->ack();

            return;
        }

        Log::warning('Requeueing raw-measurement after transient failure', [
            'message_outcome' => 'requeued_retry',
            'retry_count'     => $retryCount + 1,
            'trace_id'        => $payload['trace_id'] ?? null,
            'error'           => $exception->getMessage(),
        ]);

        sleep((int) config('services.rabbitmq.retry_delay'));

        $retried = new AMQPMessage($message->body, [
            'delivery_mode'       => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'application_headers' => TraceContextCarrier::buildRetryHeaders($message, $retryCount + 1),
        ]);
        $channel->basic_publish($retried, '', $queue);
        $message->ack();
    }

    private function retryCount(AMQPMessage $message): int
    {
        return (int) (TraceContextCarrier::applicationHeadersFromMessage($message)['x-retry-count'] ?? 0);
    }
}
