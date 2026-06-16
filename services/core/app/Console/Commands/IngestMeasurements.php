<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\IngestMeasurements\IngestMeasurementsHandler;
use Illuminate\Console\Command;

final class IngestMeasurements extends Command
{
    protected $signature   = 'core:ingest-measurements';
    protected $description = 'Fetch the current reading for every station and publish it to the raw-measurements queue';

    public function handle(IngestMeasurementsHandler $handler): void
    {
        $this->info('[core] Ingesting measurements from climate providers...');

        $handler->handle();

        $this->info('[core] Ingestion tick completed.');
    }
}
