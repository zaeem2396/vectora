<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Core;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Core\Http\PineconeHttpTransport;
use Vectora\Pinecone\Core\Http\RetryPolicy;
use Vectora\Pinecone\Tests\Support\RecordingPineconeMetrics;

final class PineconeHttpTransportMetricsRetryTest extends TestCase
{
    public function test_records_outcome_once_after_retry_then_success(): void
    {
        $http = new MockHttpClient;
        $http->responses[] = new Response(503, [], '');
        $http->responses[] = new Response(200, [], '{}');
        $metrics = new RecordingPineconeMetrics;
        $t = new PineconeHttpTransport(
            $http,
            PineconeTestFactories::httpFactory(),
            PineconeTestFactories::httpFactory(),
            'k',
            '2025-10',
            new RetryPolicy(maxAttempts: 3, initialDelayMs: 1, maxDelayMs: 5),
            null,
            $metrics
        );

        $t->get('https://h.example', '/describe_index_stats');

        $this->assertSame(1, $metrics->outcomeCalls);
        $this->assertSame(200, $metrics->lastStatus);
        $this->assertCount(2, $http->requests);
    }
}
