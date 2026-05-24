<?php

declare(strict_types=1);

use App\Domain\WeatherStation\Clients\StationClient;
use App\Domain\WeatherStation\StationSummary;
use App\Domain\WeatherStation\ValueObjects\StationId;
use App\Infrastructure\Http\Clients\CoreStationClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

test('resolves StationClient as CoreStationClient from container', function () {
    expect(app(StationClient::class))->toBeInstanceOf(CoreStationClient::class);
});

test('findById returns StationSummary when station exists', function () {
    $stationId = '00000000-0000-4000-a000-000000000001';

    Http::fake([
        'http://core/api/stations/*' => Http::response([
            'id'          => $stationId,
            'ownerId'     => '00000000-0000-4000-a000-000000000002',
            'stationName' => 'Central Buenos Aires',
            'latitude'    => -34.6,
            'longitude'   => -58.4,
            'sensorModel' => 'Davis',
            'status'      => 'active',
        ], 200),
    ]);

    $summary = app(StationClient::class)->findById(StationId::fromString($stationId));

    expect($summary)->toBeInstanceOf(StationSummary::class)
        ->and($summary->stationId)->toBe($stationId)
        ->and($summary->stationName)->toBe('Central Buenos Aires');

    Http::assertSent(function ($request) use ($stationId) {
        return $request->url() === "http://core/api/stations/{$stationId}"
            && $request->method() === 'GET';
    });
});

test('findById returns null when station not found', function () {
    $stationId = '00000000-0000-4000-a000-000000000099';

    Http::fake([
        'http://core/api/stations/*' => Http::response(['message' => 'Station not found.'], 404),
    ]);

    $summary = app(StationClient::class)->findById(StationId::fromString($stationId));

    expect($summary)->toBeNull();
});
