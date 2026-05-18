<?php

declare(strict_types=1);

namespace App\Domain\Measurement\Enums;

use App\Domain\Measurement\ValueObjects\AtmosphericPressure;
use App\Domain\Measurement\ValueObjects\Humidity;
use App\Domain\Measurement\ValueObjects\Temperature;

enum AlertType: string
{
    case None             = 'none';
    case ExtremeHeat      = 'extreme_heat';
    case Frost            = 'frost';
    case Storm            = 'storm';
    case CriticalHumidity = 'critical_humidity';

    public function matches(Temperature $temperature, Humidity $humidity, AtmosphericPressure $pressure): bool
    {
        return match ($this) {
            self::ExtremeHeat      => $temperature->value() > 40.0,
            self::Frost            => $temperature->value() < 0.0,
            self::Storm            => $pressure->value() < 980.0,
            self::CriticalHumidity => $humidity->value() > 90.0,
            self::None             => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::None             => 'None',
            self::ExtremeHeat      => 'Extreme Heat',
            self::Frost            => 'Frost',
            self::Storm            => 'Storm',
            self::CriticalHumidity => 'Critical Humidity',
        };
    }

    /** @return self[] */
    public static function fromReadings(
        Temperature         $temperature,
        Humidity            $humidity,
        AtmosphericPressure $pressure,
    ): array {
        $alerts = array_values(array_filter(
            self::cases(),
            fn(self $type) => $type->matches($temperature, $humidity, $pressure),
        ));

        return empty($alerts) ? [self::None] : $alerts;
    }
}