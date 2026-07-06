<?php

declare(strict_types=1);

namespace App\Infrastructure\Observability;

use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\ContextInterface;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

final class TraceContextCarrier
{
    /**
     * @return array<string, string>
     */
    public static function injectIntoAmqpHeaders(): array
    {
        if (! config('services.observability.otel_enabled')) {
            return [];
        }

        $carrier = [];
        TraceContextPropagator::getInstance()->inject($carrier);

        return $carrier;
    }

    public static function extractFromAmqpHeaders(?AMQPTable $headers): ContextInterface
    {
        if (! config('services.observability.otel_enabled') || $headers === null) {
            return Context::getCurrent();
        }

        return TraceContextPropagator::getInstance()->extract($headers->getNativeData());
    }

    public static function traceIdFromContext(ContextInterface $context): ?string
    {
        $spanContext = Span::fromContext($context)->getContext();

        if (! $spanContext->isValid()) {
            return null;
        }

        return $spanContext->getTraceId();
    }

    /**
     * @return array<string, mixed>
     */
    public static function applicationHeadersFromMessage(AMQPMessage $message): array
    {
        $headers = $message->get_properties()['application_headers'] ?? null;

        if ($headers instanceof AMQPTable) {
            return $headers->getNativeData();
        }

        return [];
    }

    public static function buildRetryHeaders(AMQPMessage $message, int $nextRetryCount): AMQPTable
    {
        return new AMQPTable(array_merge(
            self::applicationHeadersFromMessage($message),
            ['x-retry-count' => $nextRetryCount],
        ));
    }
}
