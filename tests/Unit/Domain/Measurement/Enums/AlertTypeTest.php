<?php

declare(strict_types=1);

use App\Domain\Measurement\Enums\AlertType;
use App\Domain\Measurement\ValueObjects\AtmosphericPressure;
use App\Domain\Measurement\ValueObjects\Humidity;
use App\Domain\Measurement\ValueObjects\Temperature;

// -------------------------------------------------------------------------
// Alert detection
// -------------------------------------------------------------------------

test('detects extreme heat when temperature is above 40', function () {
    $alerts = AlertType::fromReadings(new Temperature(40.1), new Humidity(50.0), new AtmosphericPressure(1013.0));

    expect($alerts)->toContain(AlertType::ExtremeHeat);
});

test('does not trigger extreme heat at exactly 40', function () {
    $alerts = AlertType::fromReadings(new Temperature(40.0), new Humidity(50.0), new AtmosphericPressure(1013.0));

    expect($alerts)->toBe([AlertType::None]);
});

test('detects frost when temperature is below 0', function () {
    $alerts = AlertType::fromReadings(new Temperature(-0.1), new Humidity(50.0), new AtmosphericPressure(1013.0));

    expect($alerts)->toContain(AlertType::Frost);
});

test('does not trigger frost at exactly 0', function () {
    $alerts = AlertType::fromReadings(new Temperature(0.0), new Humidity(50.0), new AtmosphericPressure(1013.0));

    expect($alerts)->toBe([AlertType::None]);
});

test('detects storm when pressure is below 980', function () {
    $alerts = AlertType::fromReadings(new Temperature(20.0), new Humidity(50.0), new AtmosphericPressure(979.9));

    expect($alerts)->toContain(AlertType::Storm);
});

test('does not trigger storm at exactly 980', function () {
    $alerts = AlertType::fromReadings(new Temperature(20.0), new Humidity(50.0), new AtmosphericPressure(980.0));

    expect($alerts)->toBe([AlertType::None]);
});

test('detects critical humidity when humidity is above 90', function () {
    $alerts = AlertType::fromReadings(new Temperature(20.0), new Humidity(90.1), new AtmosphericPressure(1013.0));

    expect($alerts)->toContain(AlertType::CriticalHumidity);
});

test('does not trigger critical humidity at exactly 90', function () {
    $alerts = AlertType::fromReadings(new Temperature(20.0), new Humidity(90.0), new AtmosphericPressure(1013.0));

    expect($alerts)->toBe([AlertType::None]);
});

test('returns none when no conditions are met', function () {
    $alerts = AlertType::fromReadings(new Temperature(20.0), new Humidity(50.0), new AtmosphericPressure(1013.0));

    expect($alerts)->toBe([AlertType::None]);
});

// -------------------------------------------------------------------------
// Multiple simultaneous alerts
// -------------------------------------------------------------------------

test('detects frost and critical humidity simultaneously', function () {
    $alerts = AlertType::fromReadings(new Temperature(-5.0), new Humidity(95.0), new AtmosphericPressure(1013.0));

    expect($alerts)
        ->toContain(AlertType::Frost)
        ->toContain(AlertType::CriticalHumidity)
        ->not->toContain(AlertType::None);
});

test('detects storm and critical humidity simultaneously', function () {
    $alerts = AlertType::fromReadings(new Temperature(20.0), new Humidity(95.0), new AtmosphericPressure(950.0));

    expect($alerts)
        ->toContain(AlertType::Storm)
        ->toContain(AlertType::CriticalHumidity);
});

// -------------------------------------------------------------------------
// Labels
// -------------------------------------------------------------------------

test('each alert type returns the correct label', function () {
    expect(AlertType::None->label())->toBe('None')
        ->and(AlertType::ExtremeHeat->label())->toBe('Extreme Heat')
        ->and(AlertType::Frost->label())->toBe('Frost')
        ->and(AlertType::Storm->label())->toBe('Storm')
        ->and(AlertType::CriticalHumidity->label())->toBe('Critical Humidity');
});