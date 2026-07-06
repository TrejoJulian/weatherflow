<?php

declare(strict_types=1);

use App\Infrastructure\Messaging\RabbitMQEventPublisher;
use OpenTelemetry\SDK\Trace\SpanExporter\InMemorySpanExporterFactory;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;

test('publish injects traceparent into application_headers when a span is active', function () {
    config(['services.observability.otel_enabled' => true]);

    $queue = 'publisher-trace-test-'.uniqid('', true);
    purgeCoreRabbitMQQueue($queue);

    $tracer = TracerProvider::builder()
        ->addSpanProcessor(new SimpleSpanProcessor((new InMemorySpanExporterFactory())->create()))
        ->build()
        ->getTracer('test');

    $span = $tracer->spanBuilder('amqp.publish')->startSpan();
    $scope = $span->activate();

    try {
        $expectedTraceId = $span->getContext()->getTraceId();

        (new RabbitMQEventPublisher())->publish($queue, ['event' => 'TestEvent']);

        $headers = peekCoreMessageHeadersFromQueue($queue);

        expect($headers)->not->toBeNull()
            ->and($headers)->toHaveKey('traceparent')
            ->and($headers['traceparent'])->toStartWith('00-'.$expectedTraceId.'-');
    } finally {
        $scope->detach();
        $span->end();
    }
});
