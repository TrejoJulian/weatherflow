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
    ) {}

    /**
     * @return array{station_id: string, station_name: string, temperature: float, reported_at: string}
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
        ];
    }
}
