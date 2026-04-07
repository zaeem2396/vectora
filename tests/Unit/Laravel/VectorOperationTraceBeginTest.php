<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Laravel;

use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Laravel\Observability\VectorOperationTrace;

final class VectorOperationTraceBeginTest extends TestCase
{
    protected function tearDown(): void
    {
        VectorOperationTrace::clear();
        parent::tearDown();
    }

    public function test_begin_and_current_without_laravel_app_use_process_fallback(): void
    {
        $id = VectorOperationTrace::begin();
        $this->assertSame(16, strlen($id));
        $this->assertTrue(ctype_xdigit($id));
        $this->assertSame($id, VectorOperationTrace::current());
    }
}
