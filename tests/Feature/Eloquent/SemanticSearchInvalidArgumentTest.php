<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Eloquent;

use Vectora\Pinecone\Laravel\Exceptions\SemanticSearchInvalidArgumentException;
use Vectora\Pinecone\Tests\Fixtures\EmbeddableArticle;

final class SemanticSearchInvalidArgumentTest extends EmbeddingsFeatureTestCase
{
    public function test_semantic_search_throws_clear_exception_for_invalid_top_k(): void
    {
        $this->expectException(SemanticSearchInvalidArgumentException::class);
        $this->expectExceptionMessage('Semantic search topK');

        EmbeddableArticle::semanticSearch('query', 0);
    }
}
