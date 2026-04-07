<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Laravel;

use Illuminate\Support\Facades\Event;
use Vectora\Pinecone\Contracts\EmbeddingDriver;
use Vectora\Pinecone\Laravel\Events\EmbeddingCallFinished;
use Vectora\Pinecone\Laravel\Observability\VectorOperationTrace;

final class ObservabilityV2EmbeddingCallFinishedTest extends PineconeFeatureTestCase
{
    protected function tearDown(): void
    {
        VectorOperationTrace::clear();
        parent::tearDown();
    }

    public function test_dispatches_embedding_event_when_observability_v2_enabled(): void
    {
        $this->mergePineconeConfig([
            'observability_v2' => [
                'enabled' => true,
                'embedding_events' => true,
                'llm_events' => false,
            ],
            'embeddings' => [
                'default' => 'deterministic',
                'cache' => ['enabled' => false],
            ],
        ]);

        Event::fake([EmbeddingCallFinished::class]);

        $tid = VectorOperationTrace::begin();
        $this->app->make(EmbeddingDriver::class)->embed('hello');

        Event::assertDispatched(EmbeddingCallFinished::class, function (EmbeddingCallFinished $e) use ($tid): bool {
            return $e->traceId === $tid
                && $e->driverName === 'deterministic'
                && $e->operation === 'embed'
                && $e->batchSize === 1;
        });
    }
}
