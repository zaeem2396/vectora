<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Eloquent;

use Vectora\Pinecone\Laravel\Jobs\SyncModelEmbeddingJob;
use Vectora\Pinecone\Tests\Fixtures\EmbeddableArticle;

final class SyncModelEmbeddingJobHandleTest extends EmbeddingsFeatureTestCase
{
    public function test_job_handle_upserts_embedding(): void
    {
        $article = EmbeddableArticle::withoutEvents(fn () => EmbeddableArticle::create([
            'title' => 'Job',
            'body' => 'Test',
        ]));
        $this->recordingStore->upsertRequests = [];

        (new SyncModelEmbeddingJob($article))->handle();

        $this->assertCount(1, $this->recordingStore->upsertRequests);
    }
}
