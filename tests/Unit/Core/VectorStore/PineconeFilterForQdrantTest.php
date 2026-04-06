<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Core\VectorStore;

use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Core\VectorStore\Support\PineconeFilterForQdrant;

final class PineconeFilterForQdrantTest extends TestCase
{
    public function test_eq_maps_to_match(): void
    {
        $out = PineconeFilterForQdrant::convert(['t' => ['$eq' => 'hello']]);
        $this->assertIsArray($out);
        $this->assertArrayHasKey('must', $out);
    }

    public function test_null_returns_null(): void
    {
        $this->assertNull(PineconeFilterForQdrant::convert(null));
    }
}
