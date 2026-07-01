<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Resilience;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Configuration;
use Ackintosh\Ganesha\Storage\AdapterInterface;
use Ackintosh\Ganesha\StrategyInterface;

final class GaneshaTestDoubles
{
    public static function alwaysAvailable(): Ganesha
    {
        return new Ganesha(new AlwaysAvailableStrategy);
    }

    public static function openCircuit(): Ganesha
    {
        return new Ganesha(new OpenCircuitStrategy);
    }

    /**
     * @return array{0: Ganesha, 1: RecordingStrategy}
     */
    public static function recording(): array
    {
        $strategy = new RecordingStrategy;

        return [new Ganesha($strategy), $strategy];
    }
}

final class AlwaysAvailableStrategy implements StrategyInterface
{
    public static function create(AdapterInterface $adapter, Configuration $configuration): StrategyInterface
    {
        return new self;
    }

    public function recordSuccess(string $service): ?int
    {
        return null;
    }

    public function recordFailure(string $service): int
    {
        return Ganesha::STATUS_CALMED_DOWN;
    }

    public function isAvailable(string $service): bool
    {
        return true;
    }

    public function reset(): void
    {
    }
}

final class OpenCircuitStrategy implements StrategyInterface
{
    public static function create(AdapterInterface $adapter, Configuration $configuration): StrategyInterface
    {
        return new self;
    }

    public function recordSuccess(string $service): ?int
    {
        return null;
    }

    public function recordFailure(string $service): int
    {
        return Ganesha::STATUS_TRIPPED;
    }

    public function isAvailable(string $service): bool
    {
        return false;
    }

    public function reset(): void
    {
    }
}

final class RecordingStrategy implements StrategyInterface
{
    public int $failureCount = 0;

    public int $successCount = 0;

    public static function create(AdapterInterface $adapter, Configuration $configuration): StrategyInterface
    {
        return new self;
    }

    public function recordSuccess(string $service): ?int
    {
        $this->successCount++;

        return null;
    }

    public function recordFailure(string $service): int
    {
        $this->failureCount++;

        return Ganesha::STATUS_CALMED_DOWN;
    }

    public function isAvailable(string $service): bool
    {
        return true;
    }

    public function reset(): void
    {
        $this->failureCount = 0;
        $this->successCount = 0;
    }
}
