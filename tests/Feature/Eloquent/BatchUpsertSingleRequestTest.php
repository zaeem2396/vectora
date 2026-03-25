<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Eloquent;

use Vectora\Pinecone\Tests\Fixtures\EmbeddableArticle;

final class BatchUpsertSingleRequestTest extends EmbeddingsFeatureTestCase
{
    public function test_batch_sync_uses_one_upsert_with_multiple_vectors(): void
    {
        $a = EmbeddableArticle::withoutEvents(fn () => EmbeddableArticle::create(['title' => 'A', 'body' => '1']));
        $b = EmbeddableArticle::withoutEvents(fn () => EmbeddableArticle::create(['title' => 'B', 'body' => '2']));

        EmbeddableArticle::upsertVectorEmbeddingsForModels([$a, $b]);

        $this->assertCount(1, $this->recordingStore->upsertRequests);
        $this->assertCount(2, $this->recordingStore->upsertRequests[0]->vectors);
    }
}
