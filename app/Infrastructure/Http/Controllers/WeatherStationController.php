<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\WeatherStation\CreateStation\CreateStationCommand;
use App\Application\WeatherStation\CreateStation\CreateStationHandler;
use App\Application\WeatherStation\DeleteStation\DeleteStationCommand;
use App\Application\WeatherStation\DeleteStation\DeleteStationHandler;
use App\Application\WeatherStation\GetAllStations\GetAllStationsHandler;
use App\Application\WeatherStation\GetAllStations\GetAllStationsQuery;
use App\Application\WeatherStation\GetStation\GetStationHandler;
use App\Application\WeatherStation\GetStation\GetStationQuery;
use App\Application\WeatherStation\UpdateStation\UpdateStationCommand;
use App\Application\WeatherStation\UpdateStation\UpdateStationHandler;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Domain\WeatherStation\Exceptions\StationNotFoundException;
use App\Infrastructure\Http\Requests\CreateStationRequest;
use App\Infrastructure\Http\Requests\UpdateStationRequest;
use Illuminate\Http\JsonResponse;

final class WeatherStationController
{
    public function __construct(
        private readonly CreateStationHandler    $createHandler,
        private readonly GetStationHandler       $getHandler,
        private readonly GetAllStationsHandler   $getAllHandler,
        private readonly UpdateStationHandler    $updateHandler,
        private readonly DeleteStationHandler    $deleteHandler,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->getAllHandler->handle(new GetAllStationsQuery()));
    }

    public function show(string $id): JsonResponse
    {
        try {
            return response()->json($this->getHandler->handle(new GetStationQuery($id)));
        } catch (StationNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function store(CreateStationRequest $request): JsonResponse
    {
        try {
            $station = $this->createHandler->handle(new CreateStationCommand(
                ownerId:     $request->input('owner_id'),
                stationName: $request->input('station_name'),
                latitude:    (float) $request->input('latitude'),
                longitude:   (float) $request->input('longitude'),
                sensorModel: $request->input('sensor_model'),
                status:      $request->input('status'),
            ));

            return response()->json($station, 201);
        } catch (UserNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function update(UpdateStationRequest $request, string $id): JsonResponse
    {
        try {
            $station = $this->updateHandler->handle(new UpdateStationCommand(
                id:          $id,
                ownerId:     $request->input('owner_id'),
                stationName: $request->input('station_name'),
                latitude:    (float) $request->input('latitude'),
                longitude:   (float) $request->input('longitude'),
                sensorModel: $request->input('sensor_model'),
                status:      $request->input('status'),
            ));

            return response()->json($station);
        } catch (StationNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (UserNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $this->deleteHandler->handle(new DeleteStationCommand($id));

            return response()->json(null, 204);
        } catch (StationNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }
}
