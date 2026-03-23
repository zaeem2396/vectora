<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Laravel;

use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use Vectora\Pinecone\Laravel\Events\VectorSynced;
use Vectora\Pinecone\Laravel\Jobs\UpsertVectorsJob;
use Vectora\Pinecone\Laravel\PineconeClientFactory;
use Vectora\Pinecone\Tests\Unit\Core\MockHttpClient;
use Vectora\Pinecone\Tests\Unit\Core\PineconeTestFactories;

final class UpsertVectorsJobHandlesTest extends PineconeFeatureTestCase
{
    protected function defineEnvironment($app): void
    {
        $this->mergePineconeConfig([
            'api_key' => 'test-key',
            'indexes' => [
                'default' => ['host' => 'https://idx.test', 'namespace' => ''],
            ],
        ], $app);
    }

    public function test_dispatches_vector_synced_on_success(): void
    {
        Event::fake([VectorSynced::class]);

        $http = new MockHttpClient;
        $http->responses[] = new Response(200, [], '{"upsertedCount":1}');
        $transport = PineconeTestFactories::transport($http);

        $this->app->afterResolving(PineconeClientFactory::class, function (PineconeClientFactory $f) use ($transport): void {
            $f->setTransport($transport);
        });

        $job = new UpsertVectorsJob([
            ['id' => 'a', 'values' => [0.1, 0.2]],
        ], null, null);
        $job->handle($this->app->make(PineconeClientFactory::class));

        Event::assertDispatched(VectorSynced::class, function (VectorSynced $e): bool {
            return $e->operation === 'upsert' && ($e->context['count'] ?? 0) === 1;
        });
    }
}
