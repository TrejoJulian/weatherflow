<?php

declare(strict_types=1);

namespace App\Domain\User\Exceptions;

use InvalidArgumentException;

final class InvalidEmailException extends InvalidArgumentException
{
    public function __construct(string $email)
    {
        parent::__construct("Invalid email address: '{$email}'");
    }
}
