<?php

declare(strict_types=1);

namespace App\Infrastructure\Metrics;

use App\Application\Contracts\MetricsRecorder;
use Prometheus\CollectorRegistry;

final class PrometheusMetricsRecorder implements MetricsRecorder
{
    public function __construct(
        private readonly CollectorRegistry $registry,
    ) {}

    public function incrementMeasurementsIngested(string $source): void
    {
        $this->registry
            ->getOrRegisterCounter('measurements', 'ingested_total', 'Total measurements persisted by source', ['source'])
            ->inc([$source]);
    }

    public function incrementAlertTriggered(string $type): void
    {
        $this->registry
            ->getOrRegisterCounter('alerts', 'triggered_total', 'Total domain alerts published by type', ['type'])
            ->inc([$type]);
    }
}
