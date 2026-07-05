<?php

declare(strict_types=1);

namespace App\Application\Contracts;

interface MetricsRecorder
{
    /**
     * One record per logical OWM call (retries are not counted individually).
     * The duration is only known when the network was actually touched, so it is
     * null on the circuit-open short-circuit.
     *
     * @param 'success'|'error'|'circuit_open' $outcome
     */
    public function recordOwmRequest(string $outcome, ?float $durationSeconds = null): void;

    public function incrementIngestionError(string $stationId): void;

    public function setBreakerOpen(bool $open): void;

    public function incrementCurrentTemperatureFallback(): void;
}
