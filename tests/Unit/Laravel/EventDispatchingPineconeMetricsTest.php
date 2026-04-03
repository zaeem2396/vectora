<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Laravel;

use GuzzleHttp\Psr7\Request;
use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Laravel\Events\PineconeHttpRequestFinished;
use Vectora\Pinecone\Laravel\Observability\EventDispatchingPineconeMetrics;

final class EventDispatchingPineconeMetricsTest extends TestCase
{
    public function test_dispatch_http_outcome_dispatches_finished_event(): void
    {
        $dispatched = null;
        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function (object $e) use (&$dispatched): void {
                $dispatched = $e;
            });

        $m = new EventDispatchingPineconeMetrics($dispatcher);
        $req = new Request('POST', 'https://x.test/vectors/upsert');
        $m->recordHttpOutcome($req, 0.12, 201, 'a1b2c3d4e5f67890');

        $this->assertInstanceOf(PineconeHttpRequestFinished::class, $dispatched);
        $this->assertSame(201, $dispatched->httpStatus);
        $this->assertNull($dispatched->failureClass);
        $this->assertSame('/vectors/upsert', $dispatched->path);
        $this->assertSame('a1b2c3d4e5f67890', $dispatched->correlationId);
    }

    public function test_dispatch_transport_failure_sets_failure_class(): void
    {
        $dispatched = null;
        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function (object $e) use (&$dispatched): void {
                $dispatched = $e;
            });

        $m = new EventDispatchingPineconeMetrics($dispatcher);
        $req = new Request('GET', 'https://x.test/stats');
        $m->recordTransportFailure($req, 1.0, \RuntimeException::class, 'abc1234567890abc');

        $this->assertInstanceOf(PineconeHttpRequestFinished::class, $dispatched);
        $this->assertNull($dispatched->httpStatus);
        $this->assertSame(\RuntimeException::class, $dispatched->failureClass);
    }
}
