<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Eloquent;

use Illuminate\Support\Facades\Bus;
use Vectora\Pinecone\Laravel\Jobs\DeleteVectorsJob;
use Vectora\Pinecone\Tests\Fixtures\EmbeddableArticle;

final class QueuedDeleteDispatchesDeleteVectorsJobTest extends EmbeddingsFeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->mergePineconeConfig([
            'eloquent' => ['default_sync' => 'queued'],
        ]);
    }

    public function test_dispatches_delete_job_on_force_delete(): void
    {
        $article = EmbeddableArticle::withoutEvents(fn () => EmbeddableArticle::create([
            'title' => 'A',
            'body' => 'B',
        ]));

        Bus::fake();
        $article->forceDelete();

        Bus::assertDispatched(DeleteVectorsJob::class);
    }
}
