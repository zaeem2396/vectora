<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Eloquent;

use Vectora\Pinecone\Tests\Fixtures\EmbeddableArticle;

final class ReindexAllChunksTest extends EmbeddingsFeatureTestCase
{
    public function test_reindex_processes_in_chunks(): void
    {
        EmbeddableArticle::withoutEvents(function (): void {
            EmbeddableArticle::create(['title' => 'A', 'body' => '1']);
            EmbeddableArticle::create(['title' => 'B', 'body' => '2']);
            EmbeddableArticle::create(['title' => 'C', 'body' => '3']);
        });

        EmbeddableArticle::reindexAllEmbeddings(2);

        $this->assertCount(2, $this->recordingStore->upsertRequests);
        $this->assertCount(2, $this->recordingStore->upsertRequests[0]->vectors);
        $this->assertCount(1, $this->recordingStore->upsertRequests[1]->vectors);
    }
}
