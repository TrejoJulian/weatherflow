<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Messaging;

use App\Application\Contracts\EventPublisher;

final class FakeEventPublisher implements EventPublisher
{
    /** @var array<array{queue: string, payload: array}> */
    private array $published = [];

    public function publish(string $queue, array $payload): void
    {
        $this->published[] = ['queue' => $queue, 'payload' => $payload];
    }

    public function wasPublishedTo(string $queue): bool
    {
        return array_any($this->published, fn(array $event) => $event['queue'] === $queue);
    }

    /** @return array[] */
    public function getPublishedTo(string $queue): array
    {
        return array_values(
            array_filter($this->published, fn(array $event) => $event['queue'] === $queue)
        );
    }
}
