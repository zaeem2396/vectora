<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel\Observability;

use Illuminate\Contracts\Events\Dispatcher;
use Psr\Http\Message\RequestInterface;
use Vectora\Pinecone\Contracts\PineconeMetrics;
use Vectora\Pinecone\Laravel\Events\PineconeHttpRequestFinished;

/** Dispatches {@see PineconeHttpRequestFinished} for listeners (logs, OpenTelemetry, etc.). */
final class EventDispatchingPineconeMetrics implements PineconeMetrics
{
    public function __construct(
        private readonly Dispatcher $events,
    ) {}

    public function recordHttpOutcome(
        RequestInterface $request,
        float $durationSeconds,
        int $statusCode,
        string $correlationId,
    ): void {
        $this->events->dispatch(new PineconeHttpRequestFinished(
            $correlationId,
            $request->getMethod(),
            self::pathFromRequest($request),
            $durationSeconds,
            $statusCode,
            null,
            VectorOperationTrace::current(),
        ));
    }

    public function recordTransportFailure(
        RequestInterface $request,
        float $durationSeconds,
        string $exceptionClass,
        string $correlationId,
    ): void {
        $this->events->dispatch(new PineconeHttpRequestFinished(
            $correlationId,
            $request->getMethod(),
            self::pathFromRequest($request),
            $durationSeconds,
            null,
            $exceptionClass,
            VectorOperationTrace::current(),
        ));
    }

    private static function pathFromRequest(RequestInterface $request): string
    {
        $path = $request->getUri()->getPath();

        return $path !== '' ? $path : '/';
    }
}
