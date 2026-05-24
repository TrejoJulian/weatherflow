<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Clients;

use App\Domain\WeatherStation\Clients\StationClient;
use App\Domain\WeatherStation\StationSummary;
use App\Domain\WeatherStation\ValueObjects\StationId;
use Illuminate\Support\Facades\Http;

final class CoreStationClient implements StationClient
{
    public function findById(StationId $id): ?StationSummary
    {
        $response = Http::baseUrl(config('services.core.url'))
            ->get("/stations/{$id->value()}");

        if ($response->notFound()) {
            return null;
        }

        $response->throw();

        $data = $response->json();

        return new StationSummary(
            stationId: $data['id'],
            stationName: $data['stationName'],
        );
    }
}
