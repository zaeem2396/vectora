<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Laravel\Events;

use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Laravel\Events\PineconeHttpRequestFinished;

final class PineconeHttpRequestFinishedTraceTest extends TestCase
{
    public function test_trace_id_defaults_to_null(): void
    {
        $e = new PineconeHttpRequestFinished(
            'a1b2c3d4e5f67890',
            'GET',
            '/stats',
            0.1,
            200,
            null,
        );

        $this->assertNull($e->traceId);
    }

    public function test_accepts_optional_trace_id(): void
    {
        $e = new PineconeHttpRequestFinished(
            'a1b2c3d4e5f67890',
            'GET',
            '/stats',
            0.1,
            200,
            null,
            'abcf0123',
        );

        $this->assertSame('abcf0123', $e->traceId);
    }
}
