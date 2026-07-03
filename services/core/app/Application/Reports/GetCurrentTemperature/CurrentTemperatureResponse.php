<?php

declare(strict_types=1);

namespace App\Application\Reports\GetCurrentTemperature;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use JsonSerializable;

final class CurrentTemperatureResponse implements JsonSerializable
{
    public function __construct(
        public readonly string            $stationId,
        public readonly string            $stationName,
        public readonly float             $temperature,
        public readonly DateTimeImmutable $reportedAt,
        public readonly bool              $stale,
        public readonly string            $source,
    ) {}

    /**
     * @return array{station_id: string, station_name: string, temperature: float, reported_at: string, stale: bool, source: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'station_id'   => $this->stationId,
            'station_name' => $this->stationName,
            'temperature'  => $this->temperature,
            'reported_at'  => $this->reportedAt
                ->setTimezone(new DateTimeZone('UTC'))
                ->format(DateTimeInterface::ATOM),
            'stale'        => $this->stale,
            'source'       => $this->source,
        ];
    }
}
