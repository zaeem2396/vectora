<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Laravel\Events;

use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Laravel\Events\EmbeddingCallFinished;

final class EmbeddingCallFinishedTest extends TestCase
{
    public function test_exposes_constructor_properties(): void
    {
        $e = new EmbeddingCallFinished(
            'trace1',
            'openai',
            'embed',
            0.5,
            120,
            1,
            42,
            0.00001,
        );

        $this->assertSame('trace1', $e->traceId);
        $this->assertSame('openai', $e->driverName);
        $this->assertSame('embed', $e->operation);
        $this->assertSame(0.5, $e->durationSeconds);
        $this->assertSame(120, $e->inputCharacters);
        $this->assertSame(1, $e->batchSize);
        $this->assertSame(42, $e->totalTokens);
        $this->assertSame(0.00001, $e->estimatedCostUsd);
    }
}
