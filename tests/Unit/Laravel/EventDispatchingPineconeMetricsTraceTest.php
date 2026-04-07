<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Laravel;

use GuzzleHttp\Psr7\Request;
use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Laravel\Events\PineconeHttpRequestFinished;
use Vectora\Pinecone\Laravel\Observability\EventDispatchingPineconeMetrics;
use Vectora\Pinecone\Laravel\Observability\VectorOperationTrace;

final class EventDispatchingPineconeMetricsTraceTest extends TestCase
{
    protected function tearDown(): void
    {
        VectorOperationTrace::clear();
        parent::tearDown();
    }

    public function test_http_outcome_includes_trace_id_when_trace_started(): void
    {
        $traceId = VectorOperationTrace::begin();

        $dispatched = null;
        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function (object $e) use (&$dispatched): void {
                $dispatched = $e;
            });

        $m = new EventDispatchingPineconeMetrics($dispatcher);
        $req = new Request('GET', 'https://x.test/health');
        $m->recordHttpOutcome($req, 0.05, 200, 'c0ffee');

        $this->assertInstanceOf(PineconeHttpRequestFinished::class, $dispatched);
        $this->assertSame($traceId, $dispatched->traceId);
    }
}
