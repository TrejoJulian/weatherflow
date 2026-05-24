<?php

declare(strict_types=1);

namespace App\Domain\User\Exceptions;

use RuntimeException;

final class UserAlreadySubscribedException extends RuntimeException
{
    public function __construct(string $stationId)
    {
        parent::__construct("User is already subscribed to station: '{$stationId}'");
    }
}