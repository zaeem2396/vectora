<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Core;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Core\Http\Json;
use Vectora\Pinecone\Tests\Support\RecordingPineconeMetrics;

final class PineconeHttpTransportMetricsSuccessTest extends TestCase
{
    public function test_records_single_outcome_on_success(): void
    {
        $http = new MockHttpClient;
        $http->responses[] = new Response(200, [], Json::encode(['ok' => true]));
        $metrics = new RecordingPineconeMetrics;
        $t = PineconeTestFactories::transport($http, metrics: $metrics);

        $t->postJson('https://h.example', '/vectors/upsert', ['vectors' => []]);

        $this->assertSame(1, $metrics->outcomeCalls);
        $this->assertSame(0, $metrics->failureCalls);
        $this->assertSame(200, $metrics->lastStatus);
        $this->assertNotNull($metrics->lastCorrelationId);
        $this->assertSame(16, strlen((string) $metrics->lastCorrelationId));
    }
}
