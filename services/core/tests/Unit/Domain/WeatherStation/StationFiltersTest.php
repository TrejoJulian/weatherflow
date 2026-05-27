<?php

declare(strict_types=1);

use App\Domain\WeatherStation\ValueObjects\StationFilters;

test('default instance returns null for all getters', function () {
    $filters = new StationFilters();

    expect($filters->name())->toBeNull()
        ->and($filters->createdFrom())->toBeNull()
        ->and($filters->createdTo())->toBeNull();
});

test('instance with values returns them from getters', function () {
    $filters = new StationFilters(
        name:        'Central',
        createdFrom: '2026-01-01T00:00:00+00:00',
        createdTo:   '2026-12-31T23:59:59+00:00',
    );

    expect($filters->name())->toBe('Central')
        ->and($filters->createdFrom())->toBe('2026-01-01T00:00:00+00:00')
        ->and($filters->createdTo())->toBe('2026-12-31T23:59:59+00:00');
});
