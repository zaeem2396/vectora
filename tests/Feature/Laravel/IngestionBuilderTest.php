<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Laravel;

use Vectora\Pinecone\Laravel\Facades\Vector;

final class IngestionBuilderTest extends PineconeFeatureTestCase
{
    public function test_vector_ingest_sync_upsert_uses_memory_store(): void
    {
        $this->mergePineconeConfig([
            'vector_store' => [
                'default' => 'memory',
            ],
            'embeddings' => [
                'default' => 'deterministic',
                'drivers' => [
                    'deterministic' => [
                        'dimensions' => 8,
                    ],
                ],
            ],
        ]);

        $n = Vector::ingest()
            ->fromString('one two three four five six')
            ->chunks(10, 0)
            ->syncUpsert('ingest-test', null, 'ns-test');

        $this->assertGreaterThan(0, $n);
    }

    public function test_ingest_factory_returns_builder(): void
    {
        $this->mergePineconeConfig([
            'vector_store' => ['default' => 'memory'],
        ]);

        $b = Vector::ingest()->fromString('x')->chunks(2, 0);
        $this->assertGreaterThan(0, $b->syncUpsert('p', null, null));
    }
}
