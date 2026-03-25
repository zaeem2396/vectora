<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Eloquent;

use Vectora\Pinecone\Tests\Fixtures\EmbeddableArticle;

final class CustomVectorEmbeddingIdTest extends EmbeddingsFeatureTestCase
{
    public function test_custom_vector_id_is_used_in_upsert(): void
    {
        $article = new class extends EmbeddableArticle
        {
            public function vectorEmbeddingId(): string
            {
                return 'custom-key';
            }
        };
        $article->forceFill(['title' => 'T', 'body' => 'B'])->save();

        $this->assertSame('custom-key', $this->recordingStore->upsertRequests[0]->vectors[0]->id);
    }
}
