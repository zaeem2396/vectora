<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Support;

use Psr\Http\Message\RequestInterface;
use Vectora\Pinecone\Contracts\PineconeMetrics;

/** Test double that records the last metrics calls. */
final class RecordingPineconeMetrics implements PineconeMetrics
{
    public int $outcomeCalls = 0;

    public int $failureCalls = 0;

    public ?string $lastCorrelationId = null;

    public ?int $lastStatus = null;

    public ?string $lastFailureClass = null;

    public function recordHttpOutcome(
        RequestInterface $request,
        float $durationSeconds,
        int $statusCode,
        string $correlationId,
    ): void {
        $this->outcomeCalls++;
        $this->lastCorrelationId = $correlationId;
        $this->lastStatus = $statusCode;
        $this->lastFailureClass = null;
    }

    public function recordTransportFailure(
        RequestInterface $request,
        float $durationSeconds,
        string $exceptionClass,
        string $correlationId,
    ): void {
        $this->failureCalls++;
        $this->lastCorrelationId = $correlationId;
        $this->lastFailureClass = $exceptionClass;
        $this->lastStatus = null;
    }
}
