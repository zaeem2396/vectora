<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Core\Exception\EmbeddingException;
use Vectora\Pinecone\Core\Exception\PineconeException;

final class EmbeddingExceptionTest extends TestCase
{
    public function test_is_pinecone_exception(): void
    {
        $e = new EmbeddingException('embed failed');
        $this->assertInstanceOf(PineconeException::class, $e);
        $this->assertSame('embed failed', $e->getMessage());
    }
}
