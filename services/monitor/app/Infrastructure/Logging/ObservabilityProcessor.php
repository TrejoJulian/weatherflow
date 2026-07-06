<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use OpenTelemetry\API\Trace\Span;

final class ObservabilityProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $extra = $record->extra;
        $extra['service'] = (string) config('services.observability.service_name');

        $spanContext = Span::getCurrent()->getContext();
        if ($spanContext->isValid()) {
            $extra['trace_id'] = $spanContext->getTraceId();
        } elseif (isset($record->context['trace_id'])) {
            $extra['trace_id'] = $record->context['trace_id'];
        }

        return $record->with(extra: $extra);
    }
}
