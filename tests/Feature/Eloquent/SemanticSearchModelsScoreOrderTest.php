<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Eloquent;

use Vectora\Pinecone\DTO\QueryVectorMatch;
use Vectora\Pinecone\Tests\Fixtures\EmbeddableArticle;

final class SemanticSearchModelsScoreOrderTest extends EmbeddingsFeatureTestCase
{
    public function test_orders_models_by_query_score(): void
    {
        EmbeddableArticle::withoutEvents(function (): void {
            EmbeddableArticle::create(['title' => 'First', 'body' => '']);
            EmbeddableArticle::create(['title' => 'Second', 'body' => '']);
        });

        $this->recordingStore->queryMatches = [
            new QueryVectorMatch('2', 0.99),
            new QueryVectorMatch('1', 0.5),
        ];

        $ordered = EmbeddableArticle::semanticSearchModels('x');

        $this->assertSame([2, 1], $ordered->pluck('id')->map(fn ($id) => (int) $id)->all());
    }
}
