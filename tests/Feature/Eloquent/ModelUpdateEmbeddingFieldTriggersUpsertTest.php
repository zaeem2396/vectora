<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Eloquent;

use Vectora\Pinecone\Tests\Fixtures\EmbeddableArticle;

final class ModelUpdateEmbeddingFieldTriggersUpsertTest extends EmbeddingsFeatureTestCase
{
    public function test_re_upserts_when_embedding_field_changes(): void
    {
        $article = EmbeddableArticle::create(['title' => 'A', 'body' => 'B']);
        $this->recordingStore->upsertRequests = [];

        $article->update(['title' => 'C']);

        $this->assertCount(1, $this->recordingStore->upsertRequests);
    }
}
