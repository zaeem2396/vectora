<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Core\VectorStore;

use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Core\VectorStore\LocalMemoryVectorStore;
use Vectora\Pinecone\DTO\QueryVectorsRequest;
use Vectora\Pinecone\DTO\UpsertVectorsRequest;
use Vectora\Pinecone\DTO\VectorRecord;

final class LocalMemoryVectorStoreTest extends TestCase
{
    public function test_upsert_query_roundtrip(): void
    {
        $s = new LocalMemoryVectorStore;
        $s->upsert(new UpsertVectorsRequest([
            new VectorRecord('1', [1.0, 0.0], ['k' => 'v']),
        ], 'ns'));
        $q = $s->query(new QueryVectorsRequest(
            vector: [1.0, 0.0],
            topK: 2,
            namespace: 'ns',
            filter: null,
            includeMetadata: true,
        ));
        $this->assertCount(1, $q->matches);
        $this->assertSame('1', $q->matches[0]->id);
        $this->assertGreaterThan(0.99, $q->matches[0]->score);
    }

    public function test_capabilities_declare_memory_backend(): void
    {
        $c = (new LocalMemoryVectorStore)->vectorStoreCapabilities();
        $this->assertSame('memory', $c->backendName);
    }
}
