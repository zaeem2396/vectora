<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Contracts;

use Psr\Http\Message\RequestInterface;
use Vectora\Pinecone\Core\Http\PineconeHttpTransport;

/**
 * Optional HTTP metrics / tracing hook for {@see PineconeHttpTransport}.
 *
 * Invoked once per logical call (after retries). Use {@see $correlationId} to correlate logs and traces.
 */
interface PineconeMetrics
{
    /**
     * @param  non-empty-string  $correlationId
     */
    public function recordHttpOutcome(
        RequestInterface $request,
        float $durationSeconds,
        int $statusCode,
        string $correlationId,
    ): void;

    /**
     * Invoked when the transport gives up after retries without a final HTTP status (e.g. connection errors).
     *
     * @param  non-empty-string  $correlationId
     * @param  class-string<\Throwable>  $exceptionClass
     */
    public function recordTransportFailure(
        RequestInterface $request,
        float $durationSeconds,
        string $exceptionClass,
        string $correlationId,
    ): void;
}
