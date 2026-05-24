<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\User\CreateUser\CreateUserCommand;
use App\Application\User\CreateUser\CreateUserHandler;
use App\Application\User\DeleteUser\DeleteUserCommand;
use App\Application\User\DeleteUser\DeleteUserHandler;
use App\Application\User\GetAllUsers\GetAllUsersHandler;
use App\Application\User\GetAllUsers\GetAllUsersQuery;
use App\Application\User\GetUser\GetUserHandler;
use App\Application\User\GetUser\GetUserQuery;
use App\Application\User\SubscribeUserToStation\SubscribeUserToStationCommand;
use App\Application\User\SubscribeUserToStation\SubscribeUserToStationHandler;
use App\Application\User\UnsubscribeUserFromStation\UnsubscribeUserFromStationCommand;
use App\Application\User\UnsubscribeUserFromStation\UnsubscribeUserFromStationHandler;
use App\Application\User\UpdateUser\UpdateUserCommand;
use App\Application\User\UpdateUser\UpdateUserHandler;
use App\Domain\User\Exceptions\DuplicateEmailException;
use App\Domain\User\Exceptions\UserAlreadySubscribedException;
use App\Domain\User\Exceptions\UserHasStationsException;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Domain\WeatherStation\Exceptions\StationNotFoundException;
use App\Infrastructure\Http\Requests\CreateUserRequest;
use App\Infrastructure\Http\Requests\SubscribeUserToStationRequest;
use App\Infrastructure\Http\Requests\UpdateUserRequest;
use Illuminate\Http\JsonResponse;

final class UserController
{
    public function __construct(
        private readonly CreateUserHandler               $createHandler,
        private readonly GetUserHandler                  $getHandler,
        private readonly GetAllUsersHandler              $getAllHandler,
        private readonly UpdateUserHandler               $updateHandler,
        private readonly DeleteUserHandler               $deleteHandler,
        private readonly SubscribeUserToStationHandler   $subscribeHandler,
        private readonly UnsubscribeUserFromStationHandler $unsubscribeHandler,
    ) {}

    public function index(): JsonResponse
    {
        $users = $this->getAllHandler->handle(new GetAllUsersQuery());

        return response()->json($users);
    }

    public function show(string $id): JsonResponse
    {
        try {
            $user = $this->getHandler->handle(new GetUserQuery($id));

            return response()->json($user);
        } catch (UserNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function store(CreateUserRequest $request): JsonResponse
    {
        try {
            $user = $this->createHandler->handle(new CreateUserCommand(
                email: $request->input('email'),
                firstName: $request->input('first_name'),
                lastName: $request->input('last_name'),
            ));

            return response()->json($user, 201);
        } catch (DuplicateEmailException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }

    public function update(UpdateUserRequest $request, string $id): JsonResponse
    {
        try {
            $user = $this->updateHandler->handle(new UpdateUserCommand(
                id: $id,
                email: $request->input('email'),
                firstName: $request->input('first_name'),
                lastName: $request->input('last_name'),
            ));

            return response()->json($user);
        } catch (UserNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (DuplicateEmailException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $this->deleteHandler->handle(new DeleteUserCommand($id));

            return response()->json(null, 204);
        } catch (UserNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (UserHasStationsException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }

    public function subscribe(SubscribeUserToStationRequest $request, string $userId): JsonResponse
    {
        try {
            $user = $this->subscribeHandler->handle(new SubscribeUserToStationCommand(
                userId:    $userId,
                stationId: $request->input('station_id'),
            ));

            return response()->json($user);
        } catch (UserNotFoundException|StationNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (UserAlreadySubscribedException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }

    public function unsubscribe(string $userId, string $stationId): JsonResponse
    {
        try {
            $this->unsubscribeHandler->handle(new UnsubscribeUserFromStationCommand(
                userId:    $userId,
                stationId: $stationId,
            ));

            return response()->json(null, 204);
        } catch (UserNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }
}