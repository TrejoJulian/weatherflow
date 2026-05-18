<?php

declare(strict_types=1);

namespace App\Domain\Measurement\Entities;

use App\Domain\Measurement\Enums\AlertType;
use App\Domain\Measurement\ValueObjects\AtmosphericPressure;
use App\Domain\Measurement\ValueObjects\Humidity;
use App\Domain\Measurement\ValueObjects\MeasurementId;
use App\Domain\Measurement\ValueObjects\Temperature;
use App\Domain\WeatherStation\ValueObjects\StationId;
use DateTimeImmutable;

final class Measurement
{
    private function __construct(
        private readonly MeasurementId      $id,
        private readonly StationId          $stationId,
        private Temperature                 $temperature,
        private Humidity                    $humidity,
        private AtmosphericPressure         $atmosphericPressure,
        private DateTimeImmutable           $reportedAt,
        private bool                        $alertStatus,
        /** @var AlertType[] */
        private array                       $alertTypes,
    ) {}

    public static function create(
        MeasurementId       $id,
        StationId           $stationId,
        Temperature         $temperature,
        Humidity            $humidity,
        AtmosphericPressure $atmosphericPressure,
        DateTimeImmutable   $reportedAt,
    ): self {
        $alertTypes = AlertType::fromReadings($temperature, $humidity, $atmosphericPressure);

        return new self(
            $id,
            $stationId,
            $temperature,
            $humidity,
            $atmosphericPressure,
            $reportedAt,
            $alertTypes !== [AlertType::None],
            $alertTypes,
        );
    }

    public function update(
        Temperature         $temperature,
        Humidity            $humidity,
        AtmosphericPressure $atmosphericPressure,
        DateTimeImmutable   $reportedAt,
    ): void {
        $this->temperature         = $temperature;
        $this->humidity            = $humidity;
        $this->atmosphericPressure = $atmosphericPressure;
        $this->reportedAt          = $reportedAt;

        $alertTypes          = AlertType::fromReadings($temperature, $humidity, $atmosphericPressure);
        $this->alertTypes    = $alertTypes;
        $this->alertStatus   = $alertTypes !== [AlertType::None];
    }

    public function id(): MeasurementId
    {
        return $this->id;
    }

    public function stationId(): StationId
    {
        return $this->stationId;
    }

    public function temperature(): Temperature
    {
        return $this->temperature;
    }

    public function humidity(): Humidity
    {
        return $this->humidity;
    }

    public function atmosphericPressure(): AtmosphericPressure
    {
        return $this->atmosphericPressure;
    }

    public function reportedAt(): DateTimeImmutable
    {
        return $this->reportedAt;
    }

    public function alertStatus(): bool
    {
        return $this->alertStatus;
    }

    /** @return AlertType[] */
    public function alertTypes(): array
    {
        return $this->alertTypes;
    }
}