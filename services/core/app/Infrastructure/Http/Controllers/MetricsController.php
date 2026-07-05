<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use Illuminate\Http\Response;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;

final class MetricsController
{
    public function __construct(
        private readonly CollectorRegistry $registry,
    ) {}

    public function __invoke(): Response
    {
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
