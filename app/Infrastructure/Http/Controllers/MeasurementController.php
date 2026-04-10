<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\Measurement\CreateMeasurement\CreateMeasurementCommand;
use App\Application\Measurement\CreateMeasurement\CreateMeasurementHandler;
use App\Application\Measurement\DeleteMeasurement\DeleteMeasurementCommand;
use App\Application\Measurement\DeleteMeasurement\DeleteMeasurementHandler;
use App\Application\Measurement\GetAllMeasurements\GetAllMeasurementsHandler;
use App\Application\Measurement\GetAllMeasurements\GetAllMeasurementsQuery;
use App\Application\Measurement\GetMeasurement\GetMeasurementHandler;
use App\Application\Measurement\GetMeasurement\GetMeasurementQuery;
use App\Application\Measurement\UpdateMeasurement\UpdateMeasurementCommand;
use App\Application\Measurement\UpdateMeasurement\UpdateMeasurementHandler;
use App\Domain\Measurement\Exceptions\MeasurementNotFoundException;
use App\Domain\WeatherStation\Exceptions\StationNotFoundException;
use App\Infrastructure\Http\Requests\CreateMeasurementRequest;
use App\Infrastructure\Http\Requests\UpdateMeasurementRequest;
use Illuminate\Http\JsonResponse;

final class MeasurementController
{
    public function __construct(
        private readonly CreateMeasurementHandler    $createHandler,
        private readonly GetMeasurementHandler       $getHandler,
        private readonly GetAllMeasurementsHandler   $getAllHandler,
        private readonly UpdateMeasurementHandler    $updateHandler,
        private readonly DeleteMeasurementHandler    $deleteHandler,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->getAllHandler->handle(new GetAllMeasurementsQuery()));
    }

    public function show(string $id): JsonResponse
    {
        try {
            return response()->json($this->getHandler->handle(new GetMeasurementQuery($id)));
        } catch (MeasurementNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function store(CreateMeasurementRequest $request): JsonResponse
    {
        try {
            $measurement = $this->createHandler->handle(new CreateMeasurementCommand(
                stationId:           $request->input('station_id'),
                temperature:         (float) $request->input('temperature'),
                humidity:            (float) $request->input('humidity'),
                atmosphericPressure: (float) $request->input('atmospheric_pressure'),
                reportedAt:          $request->input('reported_at'),
            ));

            return response()->json($measurement, 201);
        } catch (StationNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function update(UpdateMeasurementRequest $request, string $id): JsonResponse
    {
        try {
            $measurement = $this->updateHandler->handle(new UpdateMeasurementCommand(
                id:                  $id,
                temperature:         (float) $request->input('temperature'),
                humidity:            (float) $request->input('humidity'),
                atmosphericPressure: (float) $request->input('atmospheric_pressure'),
                reportedAt:          $request->input('reported_at'),
            ));

            return response()->json($measurement);
        } catch (MeasurementNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $this->deleteHandler->handle(new DeleteMeasurementCommand($id));

            return response()->json(null, 204);
        } catch (MeasurementNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }
}