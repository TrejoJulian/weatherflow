<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Clients;

use App\Domain\WeatherStation\Clients\StationClient;
use App\Domain\WeatherStation\StationSummary;
use App\Domain\WeatherStation\ValueObjects\StationId;

final class FakeStationClient implements StationClient
{
    /** @var array<string, StationSummary> */
    private array $stations = [];

    public function seed(StationSummary ...$summaries): void
    {
        foreach ($summaries as $summary) {
            $this->stations[$summary->stationId] = $summary;
        }
    }

    public function findById(StationId $id): ?StationSummary
    {
        return $this->stations[$id->value()] ?? null;
    }
}
