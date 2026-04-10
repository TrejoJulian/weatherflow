<?php

declare(strict_types=1);

namespace App\Application\Measurement;

use App\Domain\Measurement\Entities\Measurement;
use App\Domain\Measurement\Enums\AlertType;
use DateTimeInterface;

final class MeasurementResponse
{
    public function __construct(
        public readonly string $id,
        public readonly string $stationId,
        public readonly float  $temperature,
        public readonly float  $humidity,
        public readonly float  $atmosphericPressure,
        public readonly string $reportedAt,
        public readonly bool   $alertStatus,
        /** @var string[] */
        public readonly array  $alertTypes,
    ) {}

    public static function fromEntity(Measurement $measurement): self
    {
        return new self(
            id:                  $measurement->id()->value(),
            stationId:           $measurement->stationId()->value(),
            temperature:         $measurement->temperature()->value(),
            humidity:            $measurement->humidity()->value(),
            atmosphericPressure: $measurement->atmosphericPressure()->value(),
            reportedAt:          $measurement->reportedAt()->format(DateTimeInterface::ATOM),
            alertStatus:         $measurement->alertStatus(),
            alertTypes:          array_map(fn(AlertType $type) => $type->label(), $measurement->alertTypes()),
        );
    }
}