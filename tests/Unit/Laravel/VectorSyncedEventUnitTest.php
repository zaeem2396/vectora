<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Laravel;

use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Laravel\Events\VectorSynced;

final class VectorSyncedEventUnitTest extends TestCase
{
    public function test_holds_operation_and_context(): void
    {
        $e = new VectorSynced('upsert', ['count' => 3]);
        $this->assertSame('upsert', $e->operation);
        $this->assertSame(3, $e->context['count']);
    }
}
