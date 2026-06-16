<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Tests\Feature\RefreshMongoCollections;

uses(RefreshMongoCollections::class);

const REPORT_STATION_ID = '00000000-0000-4000-a000-000000000001';
const EMPTY_STATION_ID  = '00000000-0000-4000-a000-000000000099';

beforeEach(function () {
    $this->collectionsToClean = ['measurements'];
    $this->cleanCollections();

    Http::fake([
        "http://core/api/stations/" . REPORT_STATION_ID => Http::response([
            'id'          => REPORT_STATION_ID,
            'ownerId'     => '00000000-0000-4000-a000-000000000002',
            'stationName' => 'Central Buenos Aires',
            'latitude'    => -34.6,
            'longitude'   => -58.4,
            'sensorModel' => 'Davis',
            'status'      => 'active',
        ], 200),
    ]);
});

function recentReportedAt(string $modifier = '-2 hours'): string
{
    return (new DateTimeImmutable($modifier))->format(DateTimeInterface::ATOM);
}

// -------------------------------------------------------------------------
// GET /api/reports/avg/day
// -------------------------------------------------------------------------

test('returns daily average temperature for a station', function () {
    $this->postJson('/api/measurements', measurementPayload([
        'temperature' => 10.0,
        'reported_at' => recentReportedAt('-3 hours'),
    ]))->assertStatus(201);

    $this->postJson('/api/measurements', measurementPayload([
        'temperature' => 30.0,
        'reported_at' => recentReportedAt('-1 hour'),
    ]))->assertStatus(201);

    $this->getJson('/api/reports/avg/day?station_id=' . REPORT_STATION_ID)
        ->assertStatus(200)
        ->assertJson([
            'stationId'          => REPORT_STATION_ID,
            'window'             => 'day',
            'averageTemperature' => 20.0,
            'message'            => null,
        ])
        ->assertJsonStructure(['from', 'to']);
});

test('returns null daily average when station has no measurements in window', function () {
    $this->getJson('/api/reports/avg/day?station_id=' . EMPTY_STATION_ID)
        ->assertStatus(200)
        ->assertJson([
            'stationId'          => EMPTY_STATION_ID,
            'window'             => 'day',
            'averageTemperature' => null,
            'message'            => 'No measurements found for this station in the requested time window.',
        ]);
});

test('returns 422 when station_id is missing for daily average', function () {
    $this->getJson('/api/reports/avg/day')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['station_id']);
});

test('returns 422 when station_id is invalid for daily average', function () {
    $this->getJson('/api/reports/avg/day?station_id=not-a-uuid')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['station_id']);
});

// -------------------------------------------------------------------------
// GET /api/reports/avg/week
// -------------------------------------------------------------------------

test('returns weekly average temperature for a station', function () {
    $this->postJson('/api/measurements', measurementPayload([
        'temperature' => 10.0,
        'reported_at' => recentReportedAt('-2 days'),
    ]))->assertStatus(201);

    $this->postJson('/api/measurements', measurementPayload([
        'temperature' => 30.0,
        'reported_at' => recentReportedAt('-5 days'),
    ]))->assertStatus(201);

    $this->getJson('/api/reports/avg/week?station_id=' . REPORT_STATION_ID)
        ->assertStatus(200)
        ->assertJson([
            'stationId'          => REPORT_STATION_ID,
            'window'             => 'week',
            'averageTemperature' => 20.0,
            'message'            => null,
        ])
        ->assertJsonStructure(['from', 'to']);
});

test('returns null weekly average when station has no measurements in window', function () {
    $this->getJson('/api/reports/avg/week?station_id=' . EMPTY_STATION_ID)
        ->assertStatus(200)
        ->assertJson([
            'stationId'          => EMPTY_STATION_ID,
            'window'             => 'week',
            'averageTemperature' => null,
            'message'            => 'No measurements found for this station in the requested time window.',
        ]);
});

test('returns 422 when station_id is missing for weekly average', function () {
    $this->getJson('/api/reports/avg/week')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['station_id']);
});

test('returns 422 when station_id is invalid for weekly average', function () {
    $this->getJson('/api/reports/avg/week?station_id=not-a-uuid')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['station_id']);
});
