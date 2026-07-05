<?php

declare(strict_types=1);

use App\Infrastructure\Observability\TraceContextCarrier;
use OpenTelemetry\SDK\Trace\SpanExporter\InMemorySpanExporterFactory;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Tests\TestCase;

uses(TestCase::class);

test('inject and extract round-trip preserves trace id', function () {
    config(['services.observability.otel_enabled' => true]);

    $tracer = TracerProvider::builder()
        ->addSpanProcessor(new SimpleSpanProcessor((new InMemorySpanExporterFactory())->create()))
        ->build()
        ->getTracer('test');

    $span = $tracer->spanBuilder('test.span')->startSpan();
    $scope = $span->activate();

    try {
        $expectedTraceId = $span->getContext()->getTraceId();
        $headers = TraceContextCarrier::injectIntoAmqpHeaders();

        expect($headers)->toHaveKey('traceparent')
            ->and($headers['traceparent'])->toStartWith('00-'.$expectedTraceId.'-');

        $context = TraceContextCarrier::extractFromAmqpHeaders(new AMQPTable($headers));

        expect(TraceContextCarrier::traceIdFromContext($context))->toBe($expectedTraceId);
    } finally {
        $scope->detach();
        $span->end();
    }
});

test('inject returns empty array when otel is disabled', function () {
    config(['services.observability.otel_enabled' => false]);

    $tracer = TracerProvider::builder()
        ->addSpanProcessor(new SimpleSpanProcessor((new InMemorySpanExporterFactory())->create()))
        ->build()
        ->getTracer('test');

    $span = $tracer->spanBuilder('test.span')->startSpan();
    $scope = $span->activate();

    try {
        expect(TraceContextCarrier::injectIntoAmqpHeaders())->toBe([]);
    } finally {
        $scope->detach();
        $span->end();
    }
});

test('buildRetryHeaders merges traceparent and increments retry count', function () {
    $message = new AMQPMessage('{}', [
        'application_headers' => new AMQPTable([
            'traceparent'   => '00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-01',
            'x-retry-count' => 2,
        ]),
    ]);

    $retryHeaders = TraceContextCarrier::buildRetryHeaders($message, 3)->getNativeData();

    expect($retryHeaders['traceparent'])->toBe('00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-01')
        ->and($retryHeaders['x-retry-count'])->toBe(3);
});
