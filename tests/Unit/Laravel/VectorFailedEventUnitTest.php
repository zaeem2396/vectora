<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Laravel;

use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Laravel\Events\VectorFailed;

final class VectorFailedEventUnitTest extends TestCase
{
    public function test_holds_message(): void
    {
        $e = new VectorFailed('delete', 'boom', ['i' => 1]);
        $this->assertSame('delete', $e->operation);
        $this->assertSame('boom', $e->message);
        $this->assertSame(1, $e->context['i']);
    }
}
