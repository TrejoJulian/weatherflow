<?php

declare(strict_types=1);

use App\Infrastructure\Logging\ObservabilityProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use Tests\TestCase;

uses(TestCase::class);

function observabilityLogRecord(array $context = []): LogRecord
{
    return new LogRecord(
        datetime: new DateTimeImmutable(),
        channel: 'stderr',
        level: Level::Info,
        message: 'test message',
        context: $context,
    );
}

test('injects the configured service name into extra', function () {
    config(['services.observability.service_name' => 'weatherflow-core']);

    $record = (new ObservabilityProcessor)(observabilityLogRecord());

    expect($record->extra['service'])->toBe('weatherflow-core');
});

test('promotes trace_id from context to extra when present', function () {
    config(['services.observability.service_name' => 'weatherflow-core']);

    $record = (new ObservabilityProcessor)(observabilityLogRecord(['trace_id' => 'ingest-abc123']));

    expect($record->extra['trace_id'])->toBe('ingest-abc123');
});

test('does not set trace_id in extra when absent from context', function () {
    config(['services.observability.service_name' => 'weatherflow-core']);

    $record = (new ObservabilityProcessor)(observabilityLogRecord(['station_id' => 'st-1']));

    expect($record->extra)->not->toHaveKey('trace_id')
        ->and($record->extra['service'])->toBe('weatherflow-core');
});
