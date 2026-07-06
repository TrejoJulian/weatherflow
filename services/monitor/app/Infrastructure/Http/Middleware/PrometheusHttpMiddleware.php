<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Prometheus\CollectorRegistry;
use Symfony\Component\HttpFoundation\Response;

final class PrometheusHttpMiddleware
{
    public function __construct(
        private readonly CollectorRegistry $registry,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        $response = $next($request);

        $duration = microtime(true) - $start;
        $method   = $request->method();
        // Route template (e.g. "api/reports/avg/day"), never the concrete URL
        // with IDs — keeps label cardinality bounded. Unmatched requests (404)
        // have no route, so they share a fixed label value.
        $route  = $request->route()?->uri() ?? 'unmatched';
        $status = (string) $response->getStatusCode();

        $this->registry
            ->getOrRegisterCounter('http', 'requests_total', 'Total HTTP requests', ['method', 'route', 'status'])
            ->inc([$method, $route, $status]);

        $this->registry
            ->getOrRegisterHistogram('http', 'request_duration_seconds', 'HTTP request duration in seconds', ['method', 'route'])
            ->observe($duration, [$method, $route]);

        return $response;
    }
}
