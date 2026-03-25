<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Eloquent;

use Vectora\Pinecone\Tests\Fixtures\EmbeddableArticle;

final class SemanticSearchUsesModelFilterTest extends EmbeddingsFeatureTestCase
{
    public function test_applies_default_model_metadata_filter(): void
    {
        EmbeddableArticle::semanticSearch('hello', 5);

        $this->assertNotNull($this->recordingStore->lastQueryRequest);
        $filter = $this->recordingStore->lastQueryRequest->filter;
        $this->assertIsArray($filter);
        $this->assertSame(EmbeddableArticle::class, $filter['vectora_model'] ?? null);
    }
}
