<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel\Events;

use Vectora\Pinecone\Contracts\PineconeMetrics;

/**
 * Dispatched when {@see PineconeMetrics} is enabled and a logical HTTP call completes.
 *
 * - {@see $httpStatus} is set for any final HTTP status (2xx–5xx).
 * - {@see $failureClass} is set when the client gave up without a final HTTP response (transport errors).
 */
final class PineconeHttpRequestFinished
{
    /**
     * @param  non-empty-string  $correlationId
     * @param  non-empty-string  $method
     * @param  non-empty-string  $path
     * @param  class-string<\Throwable>|null  $failureClass
     */
    public function __construct(
        public readonly string $correlationId,
        public readonly string $method,
        public readonly string $path,
        public readonly float $durationSeconds,
        public readonly ?int $httpStatus,
        public readonly ?string $failureClass,
    ) {}
}
