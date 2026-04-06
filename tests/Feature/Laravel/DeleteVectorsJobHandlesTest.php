<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Laravel;

use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use Vectora\Pinecone\Laravel\Events\VectorSynced;
use Vectora\Pinecone\Laravel\Jobs\DeleteVectorsJob;
use Vectora\Pinecone\Laravel\PineconeClientFactory;
use Vectora\Pinecone\Laravel\VectorStoreManager;
use Vectora\Pinecone\Tests\Unit\Core\MockHttpClient;
use Vectora\Pinecone\Tests\Unit\Core\PineconeTestFactories;

final class DeleteVectorsJobHandlesTest extends PineconeFeatureTestCase
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
        $http->responses[] = new Response(200, [], '{}');
        $transport = PineconeTestFactories::transport($http);

        $this->app->afterResolving(PineconeClientFactory::class, function (PineconeClientFactory $f) use ($transport): void {
            $f->setTransport($transport);
        });

        $job = new DeleteVectorsJob(ids: ['x'], index: null);
        $job->handle($this->app->make(VectorStoreManager::class));

        Event::assertDispatched(VectorSynced::class, fn (VectorSynced $e) => $e->operation === 'delete');
    }
}
