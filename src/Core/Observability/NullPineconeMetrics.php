<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Core\Observability;

use Psr\Http\Message\RequestInterface;
use Vectora\Pinecone\Contracts\PineconeMetrics;

/** No-op metrics recorder (default when metrics are disabled). */
final class NullPineconeMetrics implements PineconeMetrics
{
    public function recordHttpOutcome(
        RequestInterface $request,
        float $durationSeconds,
        int $statusCode,
        string $correlationId,
    ): void {}

    public function recordTransportFailure(
        RequestInterface $request,
        float $durationSeconds,
        string $exceptionClass,
        string $correlationId,
    ): void {}
}
