<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Metrics;

use App\Application\Contracts\MetricsRecorder;

final class FakeMetricsRecorder implements MetricsRecorder
{
    /** @var string[] */
    public array $measurementsIngested = [];

    /** @var string[] */
    public array $alertsTriggered = [];

    public function incrementMeasurementsIngested(string $source): void
    {
        $this->measurementsIngested[] = $source;
    }

    public function incrementAlertTriggered(string $type): void
    {
        $this->alertsTriggered[] = $type;
    }

    public function measurementsIngestedCount(string $source): int
    {
        return count(array_filter($this->measurementsIngested, fn (string $value) => $value === $source));
    }

    public function alertsTriggeredCount(string $type): int
    {
        return count(array_filter($this->alertsTriggered, fn (string $value) => $value === $type));
    }
}
