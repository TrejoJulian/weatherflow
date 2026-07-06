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

    public function recordOwmRequest(string $outcome, ?float $durationSeconds = null): void
    {
        $this->registry
            ->getOrRegisterCounter('owm', 'requests_total', 'Total logical OWM requests by outcome', ['outcome'])
            ->inc([$outcome]);

        if ($durationSeconds !== null) {
            // OWM latency can span a single fast call up to timeout * retries (~24s),
            // so the default HTTP buckets (max 10s) are replaced with wider ones.
            $buckets = [0.1, 0.25, 0.5, 1.0, 2.0, 4.0, 8.0, 16.0, 24.0];

            $this->registry
                ->getOrRegisterHistogram('owm', 'request_duration_seconds', 'OWM request duration in seconds', [], $buckets)
                ->observe($durationSeconds, []);
        }
    }

    public function incrementIngestionError(string $stationId): void
    {
        $this->registry
            ->getOrRegisterCounter('owm', 'ingestion_errors_total', 'OWM ingestion failures per station', ['station_id'])
            ->inc([$stationId]);
    }

    public function setBreakerOpen(bool $open): void
    {
        $this->registry
            ->getOrRegisterGauge('owm', 'breaker_state', 'OWM circuit breaker state (0=closed, 1=open)', [])
            ->set($open ? 1 : 0, []);
    }

    public function incrementCurrentTemperatureFallback(): void
    {
        $this->registry
            ->getOrRegisterCounter('current_temp', 'fallback_total', 'Current-temperature responses served from stale fallback cache', [])
            ->inc([]);
    }
}
