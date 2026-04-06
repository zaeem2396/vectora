<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Eloquent;

use Illuminate\Support\Facades\DB;
use Vectora\Pinecone\Laravel\Rag\EmbeddableRagRetriever;
use Vectora\Pinecone\Tests\Fixtures\EmbeddableArticle;

/**
 * Ensures hydration of retrieval matches uses one WHERE IN, not per-match finds.
 */
final class EmbeddableRagRetrieverBatchQueryTest extends EmbeddingsFeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->mergePineconeConfig([
            'vector_store' => ['default' => 'memory'],
        ]);
    }

    public function test_retrieve_uses_single_select_for_multiple_matches(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $row = EmbeddableArticle::query()->create([
                'title' => 'Batch',
                'body' => 'Shared keyword '.$i.' content',
            ]);
            $row->syncVectorEmbeddingNow();
        }

        $selectCount = 0;
        DB::listen(function ($query) use (&$selectCount): void {
            $sql = strtolower($query->sql);
            if (str_starts_with(trim($sql), 'select') && str_contains($sql, 'embeddable_articles')) {
                $selectCount++;
            }
        });

        $retriever = new EmbeddableRagRetriever(EmbeddableArticle::class);
        $chunks = $retriever->retrieve('Shared keyword', 5);

        $this->assertGreaterThanOrEqual(2, count($chunks));
        $this->assertSame(1, $selectCount, 'Expected one SELECT on embeddable_articles for batch hydration.');
    }
}
