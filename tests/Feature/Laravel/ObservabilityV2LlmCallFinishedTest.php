<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Laravel;

use Illuminate\Support\Facades\Event;
use Vectora\Pinecone\Contracts\LLMDriver;
use Vectora\Pinecone\Laravel\Events\LlmCallFinished;
use Vectora\Pinecone\Laravel\Observability\VectorOperationTrace;

final class ObservabilityV2LlmCallFinishedTest extends PineconeFeatureTestCase
{
    protected function tearDown(): void
    {
        VectorOperationTrace::clear();
        parent::tearDown();
    }

    public function test_dispatches_llm_event_when_observability_v2_llm_enabled(): void
    {
        $this->mergePineconeConfig([
            'observability_v2' => [
                'enabled' => true,
                'embedding_events' => false,
                'llm_events' => true,
            ],
            'llm' => [
                'default' => 'stub',
                'drivers' => [
                    'stub' => ['prefix' => 'x: '],
                ],
            ],
        ]);

        Event::fake([LlmCallFinished::class]);

        $tid = VectorOperationTrace::begin();
        $this->app->make(LLMDriver::class)->chat([['role' => 'user', 'content' => 'hi']]);

        Event::assertDispatched(LlmCallFinished::class, function (LlmCallFinished $e) use ($tid): bool {
            return $e->traceId === $tid && $e->driverName === 'stub';
        });
    }
}
