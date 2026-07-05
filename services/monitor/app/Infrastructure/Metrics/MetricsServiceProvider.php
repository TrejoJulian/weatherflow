<?php

declare(strict_types=1);

namespace App\Infrastructure\Metrics;

use Illuminate\Support\ServiceProvider;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\Redis;

final class MetricsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CollectorRegistry::class, function () {
            $redis = config('metrics.redis');

            Redis::setDefaultOptions([
                'host'     => $redis['host'],
                'port'     => $redis['port'],
                'password' => $redis['password'],
                'database' => $redis['database'],
            ]);

            // Static, app-wide key prefix: lets Core and Monitor share one Redis
            // instance without their metric keys colliding.
            Redis::setPrefix(config('metrics.prefix'));

            // Default metrics disabled so Redis is only touched on scrape, never
            // at boot — a momentary Redis hiccup must not break the whole app.
            return new CollectorRegistry(new Redis(), false);
        });
    }
}
