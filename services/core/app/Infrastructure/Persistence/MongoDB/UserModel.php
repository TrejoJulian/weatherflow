<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MongoDB;

final class UserModel extends BaseMongoModel
{
    protected $table = 'users';
    protected $primaryKey = '_id';

    protected $fillable = [
        '_id',
        'email',
        'first_name',
        'last_name',
        'subscriptions',
    ];

    protected $casts = [
        'subscriptions' => 'array',
    ];
}
