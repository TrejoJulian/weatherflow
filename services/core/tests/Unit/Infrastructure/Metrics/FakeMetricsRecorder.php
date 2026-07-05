<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Metrics;

use App\Application\Contracts\MetricsRecorder;

final class FakeMetricsRecorder implements MetricsRecorder
{
    /** @var array<array{outcome: string, duration: ?float}> */
    public array $owmRequests = [];

    /** @var string[] */
    public array $ingestionErrors = [];

    /** @var bool[] */
    public array $breakerStates = [];

    public int $currentTemperatureFallbacks = 0;

    public function recordOwmRequest(string $outcome, ?float $durationSeconds = null): void
    {
        $this->owmRequests[] = ['outcome' => $outcome, 'duration' => $durationSeconds];
    }

    public function incrementIngestionError(string $stationId): void
    {
        $this->ingestionErrors[] = $stationId;
    }

    public function setBreakerOpen(bool $open): void
    {
        $this->breakerStates[] = $open;
    }

    public function incrementCurrentTemperatureFallback(): void
    {
        $this->currentTemperatureFallbacks++;
    }

    public function owmOutcomeCount(string $outcome): int
    {
        return count(array_filter($this->owmRequests, fn (array $call) => $call['outcome'] === $outcome));
    }
}
