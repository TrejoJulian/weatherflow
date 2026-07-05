<?php

declare(strict_types=1);

namespace App\Application\Contracts;

interface MetricsRecorder
{
    /**
     * @param 'raw'|'manual' $source
     */
    public function incrementMeasurementsIngested(string $source): void;

    public function incrementAlertTriggered(string $type): void;
}
