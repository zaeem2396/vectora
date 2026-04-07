<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Laravel;

use Illuminate\Support\Facades\Event;
use Vectora\Pinecone\Contracts\EmbeddingDriver;
use Vectora\Pinecone\Laravel\Events\EmbeddingCallFinished;

final class ObservabilityV2DisabledTest extends PineconeFeatureTestCase
{
    public function test_no_embedding_event_when_observability_v2_disabled(): void
    {
        $this->mergePineconeConfig([
            'observability_v2' => [
                'enabled' => false,
                'embedding_events' => true,
            ],
            'embeddings' => [
                'default' => 'deterministic',
                'cache' => ['enabled' => false],
            ],
        ]);

        Event::fake([EmbeddingCallFinished::class]);
        $this->app->make(EmbeddingDriver::class)->embed('hello');

        Event::assertNotDispatched(EmbeddingCallFinished::class);
    }
}
