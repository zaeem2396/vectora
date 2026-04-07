<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Eloquent;

use Vectora\Pinecone\DTO\QueryVectorMatch;
use Vectora\Pinecone\Tests\Fixtures\EmbeddableArticle;

final class SemanticEloquentBuilderTest extends EmbeddingsFeatureTestCase
{
    public function test_semantic_where_restricts_to_vector_ids_and_orders_by_similarity(): void
    {
        EmbeddableArticle::withoutEvents(function (): void {
            EmbeddableArticle::create(['title' => 'A', 'body' => '']);
            EmbeddableArticle::create(['title' => 'B', 'body' => '']);
            EmbeddableArticle::create(['title' => 'C', 'body' => '']);
        });

        $this->recordingStore->queryMatches = [
            new QueryVectorMatch('3', 0.9),
            new QueryVectorMatch('1', 0.5),
        ];

        $rows = EmbeddableArticle::query()
            ->semanticWhere('test query', 10)
            ->get();

        $this->assertSame([3, 1], $rows->pluck('id')->map(fn ($id) => (int) $id)->all());
    }

    public function test_semantic_where_empty_matches_yields_no_rows(): void
    {
        EmbeddableArticle::withoutEvents(function (): void {
            EmbeddableArticle::create(['title' => 'A', 'body' => '']);
        });

        $this->recordingStore->queryMatches = [];

        $rows = EmbeddableArticle::query()->semanticWhere('none', 5)->get();

        $this->assertCount(0, $rows);
    }

    public function test_semantic_order_by_reuses_context_when_parameters_match(): void
    {
        EmbeddableArticle::withoutEvents(function (): void {
            EmbeddableArticle::create(['title' => 'A', 'body' => '']);
        });

        $this->recordingStore->queryMatches = [new QueryVectorMatch('1', 1.0)];

        $q = EmbeddableArticle::query()->semanticWhere('same', 5);
        $this->assertSame(1, $this->recordingStore->queryCallCount);
        $q->semanticOrderBy('same', 5);
        $this->assertSame(1, $this->recordingStore->queryCallCount);
    }
}
