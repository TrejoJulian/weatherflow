<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\User\Repositories\UserRepository;
use App\Infrastructure\Persistence\MongoDB\MongoUserRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepository::class, MongoUserRepository::class);
    }

    public function boot(): void {}
}
