<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Laravel;

use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Laravel\Observability\VectorOperationTrace;

final class VectorOperationTraceClearTest extends TestCase
{
    protected function tearDown(): void
    {
        VectorOperationTrace::clear();
        parent::tearDown();
    }

    public function test_clear_removes_active_trace(): void
    {
        VectorOperationTrace::begin();
        VectorOperationTrace::clear();
        $this->assertNull(VectorOperationTrace::current());
    }
}
