<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Core\VectorStore;

use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Core\VectorStore\SqliteVectorStore;
use Vectora\Pinecone\DTO\QueryVectorsRequest;
use Vectora\Pinecone\DTO\UpsertVectorsRequest;
use Vectora\Pinecone\DTO\VectorRecord;

final class SqliteVectorStoreTest extends TestCase
{
    public function test_roundtrip_on_memory_sqlite(): void
    {
        if (! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            self::markTestSkipped('SQLite PDO driver required.');
        }
        $s = SqliteVectorStore::open(':memory:');
        $s->upsert(new UpsertVectorsRequest([
            new VectorRecord('a', [0.0, 1.0], ['x' => 1]),
        ], null));
        $q = $s->query(new QueryVectorsRequest(
            vector: [0.0, 1.0],
            topK: 3,
            namespace: null,
            filter: null,
            includeMetadata: true,
        ));
        $this->assertSame('a', $q->matches[0]->id);
    }
}
