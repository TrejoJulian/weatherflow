<?php

declare(strict_types=1);

namespace App\Domain\User\Exceptions;

use RuntimeException;

final class DuplicateEmailException extends RuntimeException
{
    public function __construct(string $email)
    {
        parent::__construct("Email already in use: '{$email}'");
    }
}
