<?php

declare(strict_types=1);

namespace App\Domain\User\Exceptions;

use RuntimeException;

final class UserNotFoundException extends RuntimeException
{
    public function __construct(string $userId)
    {
        parent::__construct("User not found: '{$userId}'");
    }
}
