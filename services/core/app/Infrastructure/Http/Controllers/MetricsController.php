<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use Ackintosh\Ganesha;
use App\Application\Contracts\MetricsRecorder;
use Illuminate\Http\Response;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;

final class MetricsController
{
    private const OWM_SERVICE = 'openweather';

    public function __construct(
        private readonly CollectorRegistry $registry,
        private readonly MetricsRecorder $metrics,
        private readonly Ganesha $ganesha,
    ) {}

    public function __invoke(): Response
    {
        // Sync breaker gauge on every scrape so the series exists even before the
        // first Ganesha transition event (which only fires in-process).
        $this->metrics->setBreakerOpen(! $this->ganesha->isAvailable(self::OWM_SERVICE));

        $this->registry
            ->getOrRegisterGauge('weatherflow', 'app_info', 'Static application info', ['service'])
            ->set(1, [config('metrics.service_name')]);

        $renderer = new RenderTextFormat();

        return response(
            $renderer->render($this->registry->getMetricFamilySamples()),
            Response::HTTP_OK,
            ['Content-Type' => RenderTextFormat::MIME_TYPE],
        );
    }
}
