<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Core;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Core\Exception\ApiException;
use Vectora\Pinecone\Tests\Support\RecordingPineconeMetrics;

final class PineconeHttpTransportMetricsHttpErrorTest extends TestCase
{
    public function test_records_http_error_status_on_final_4xx(): void
    {
        $http = new MockHttpClient;
        $http->responses[] = new Response(404, [], '{"message":"missing"}');
        $metrics = new RecordingPineconeMetrics;
        $t = PineconeTestFactories::transport($http, metrics: $metrics);

        try {
            $t->delete('https://h.example', '/vectors/delete');
        } catch (ApiException) {
        }

        $this->assertSame(1, $metrics->outcomeCalls);
        $this->assertSame(404, $metrics->lastStatus);
    }
}
