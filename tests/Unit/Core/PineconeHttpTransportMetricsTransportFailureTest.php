<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Core\Http\PineconeHttpTransport;
use Vectora\Pinecone\Core\Http\RetryPolicy;
use Vectora\Pinecone\Tests\Support\RecordingPineconeMetrics;

final class PineconeHttpTransportMetricsTransportFailureTest extends TestCase
{
    public function test_records_transport_failure_after_retries_exhausted(): void
    {
        $http = new MockHttpClient;
        $http->throwOnSend = new \RuntimeException('connection reset');
        $metrics = new RecordingPineconeMetrics;
        $t = new PineconeHttpTransport(
            $http,
            PineconeTestFactories::httpFactory(),
            PineconeTestFactories::httpFactory(),
            'k',
            '2025-10',
            new RetryPolicy(maxAttempts: 2, initialDelayMs: 1, maxDelayMs: 5),
            null,
            $metrics
        );

        try {
            $t->postJson('https://h.example', '/p', []);
        } catch (\RuntimeException) {
        }

        $this->assertSame(0, $metrics->outcomeCalls);
        $this->assertSame(1, $metrics->failureCalls);
        $this->assertSame(\RuntimeException::class, $metrics->lastFailureClass);
    }
}
