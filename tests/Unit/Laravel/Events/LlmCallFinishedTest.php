<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Laravel\Events;

use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Laravel\Events\LlmCallFinished;

final class LlmCallFinishedTest extends TestCase
{
    public function test_exposes_constructor_properties(): void
    {
        $e = new LlmCallFinished(
            null,
            'stub',
            0.1,
            10,
            20,
            30,
            0.00002,
        );

        $this->assertNull($e->traceId);
        $this->assertSame('stub', $e->driverName);
        $this->assertSame(0.1, $e->durationSeconds);
        $this->assertSame(10, $e->promptTokens);
        $this->assertSame(20, $e->completionTokens);
        $this->assertSame(30, $e->totalTokens);
        $this->assertSame(0.00002, $e->estimatedCostUsd);
    }
}
