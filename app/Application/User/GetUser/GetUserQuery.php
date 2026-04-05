<?php

declare(strict_types=1);

namespace App\Application\User\GetUser;

final class GetUserQuery
{
    public function __construct(
        public readonly string $id,
    ) {}
}
