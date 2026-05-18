<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MongoDB;

use App\Domain\User\Entities\User;
use App\Domain\User\Repositories\UserRepository;
use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\WeatherStation\ValueObjects\StationId;

final class MongoUserRepository implements UserRepository
{
    public function save(User $user): void
    {
        UserModel::updateOrCreate(
            ['_id' => $user->id()->value()],
            [
                'email'         => $user->email()->value(),
                'first_name'    => $user->firstName(),
                'last_name'     => $user->lastName(),
                'subscriptions' => array_map(
                    fn (StationId $stationId) => $stationId->value(),
                    $user->subscriptions(),
                ),
            ]
        );
    }

    public function findById(UserId $id): ?User
    {
        $model = UserModel::find($id->value());

        return $model ? $this->toDomain($model) : null;
    }

    public function findByEmail(Email $email): ?User
    {
        $model = UserModel::where('email', $email->value())->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findAll(): array
    {
        return UserModel::all()
            ->map(fn (UserModel $model) => $this->toDomain($model))
            ->all();
    }

    public function delete(UserId $id): void
    {
        UserModel::destroy($id->value());
    }

    private function toDomain(UserModel $model): User
    {
        $rawSubscriptions = is_array($model->subscriptions) ? $model->subscriptions : [];

        $subscriptions = array_map(
            fn (string $stationId) => StationId::fromString($stationId),
            $rawSubscriptions,
        );

        return User::create(
            UserId::fromString($model->_id),
            new Email($model->email),
            $model->first_name,
            $model->last_name,
            $subscriptions,
        );
    }
}