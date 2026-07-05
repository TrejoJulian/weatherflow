<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

final class ObservabilityProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $extra = $record->extra;
        $extra['service'] = (string) config('services.observability.service_name');

        // Hoy el trace_id llega en el contexto de cada Log::; con OTel (Issue 6)
        // pasará a leerse del span activo.
        if (isset($record->context['trace_id'])) {
            $extra['trace_id'] = $record->context['trace_id'];
        }

        return $record->with(extra: $extra);
    }
}
