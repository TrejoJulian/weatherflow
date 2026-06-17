<?php

declare(strict_types=1);

namespace App\Application\Reports\GetDailyAverageTemperature;

use App\Application\Reports\AverageTemperatureResponse;
use App\Domain\Measurement\Repositories\MeasurementRepository;
use App\Domain\WeatherStation\ValueObjects\StationId;
use DateTimeImmutable;
use DateTimeInterface;

final class GetDailyAverageTemperatureHandler
{
    private const NO_DATA_MESSAGE = 'No measurements found for this station in the requested time window.';

    public function __construct(
        private readonly MeasurementRepository $measurementRepository,
    ) {}

    public function handle(GetDailyAverageTemperatureQuery $query): AverageTemperatureResponse
    {
        $stationId = StationId::fromString($query->stationId);
        $now       = new DateTimeImmutable();
        $from      = $now->modify('-1 day');

        $average = $this->measurementRepository->averageTemperature($stationId, $from, $now);

        return new AverageTemperatureResponse(
            stationId:           $query->stationId,
            window:              'day',
            averageTemperature:  $average,
            from:                $from->format(DateTimeInterface::ATOM),
            to:                  $now->format(DateTimeInterface::ATOM),
            message:             $average === null ? self::NO_DATA_MESSAGE : null,
        );
    }
}
