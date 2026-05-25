<?php

declare(strict_types=1);

namespace App\Application\Contracts;

interface EventPublisher
{
    public function publish(string $queue, array $payload): void;
}
