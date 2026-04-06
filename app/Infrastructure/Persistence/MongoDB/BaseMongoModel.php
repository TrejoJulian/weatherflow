<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MongoDB;

use MongoDB\Laravel\Eloquent\Model;

/**
 * Base model for all MongoDB Eloquent models in this project.
 *
 * Overrides resolveCollectionFromAttribute() to prevent Laravel 13's HasCollection
 * trait from attempting to instantiate MongoDB\Laravel\Eloquent\Model directly,
 * which fails because that class is abstract.
 *
 * @see https://github.com/mongodb/laravel-mongodb/issues — compatibility with Laravel 13
 */
abstract class BaseMongoModel extends Model
{
    protected $connection = 'mongodb';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    public function resolveCollectionFromAttribute(): ?string
    {
        return null;
    }
}
