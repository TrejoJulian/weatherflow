<?php

declare(strict_types=1);

namespace App\Domain\User\Exceptions;

use RuntimeException;

final class UserHasStationsException extends RuntimeException
{
    public function __construct(string $userId)
    {
        parent::__construct("User {$userId} owns one or more weather stations and cannot be deleted.");
    }
}
