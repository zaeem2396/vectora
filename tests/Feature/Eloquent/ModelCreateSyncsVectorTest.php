<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Eloquent;

use Vectora\Pinecone\Tests\Fixtures\EmbeddableArticle;

final class ModelCreateSyncsVectorTest extends EmbeddingsFeatureTestCase
{
    public function test_creates_vector_when_model_is_created(): void
    {
        EmbeddableArticle::create(['title' => 'Hello', 'body' => 'World']);

        $this->assertCount(1, $this->recordingStore->upsertRequests);
        $this->assertSame('1', $this->recordingStore->upsertRequests[0]->vectors[0]->id);
    }
}
