<?php

declare(strict_types=1);

namespace App\Infrastructure\Observability;

use Illuminate\Support\ServiceProvider;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Sdk;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;

final class OpenTelemetryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            TracerInterface::class,
            static fn (): TracerInterface => Globals::tracerProvider()->getTracer('weatherflow'),
        );
    }

    public function boot(): void
    {
        if (! config('services.observability.otel_enabled')) {
            return;
        }

        $transport = (new OtlpHttpTransportFactory())->create(
            config('services.observability.otel_exporter_url').'/v1/traces',
            'application/x-protobuf',
        );

        $resource = ResourceInfo::create(Attributes::create([
            'service.name' => config('services.observability.service_name'),
        ]));

        $tracerProvider = TracerProvider::builder()
            ->addSpanProcessor(new SimpleSpanProcessor(new SpanExporter($transport)))
            ->setResource($resource)
            ->build();

        Sdk::builder()
            ->setTracerProvider($tracerProvider)
            ->setPropagator(TraceContextPropagator::getInstance())
            ->setAutoShutdown(true)
            ->buildAndRegisterGlobal();
    }
}
