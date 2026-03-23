<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Laravel;

use GuzzleHttp\Psr7\Response;
use Vectora\Pinecone\Laravel\PineconeClientFactory;
use Vectora\Pinecone\Tests\Unit\Core\MockHttpClient;
use Vectora\Pinecone\Tests\Unit\Core\PineconeTestFactories;

final class PineconeFlushCommandTest extends PineconeFeatureTestCase
{
    protected function defineEnvironment($app): void
    {
        $this->mergePineconeConfig([
            'api_key' => 'test-key',
            'indexes' => [
                'default' => ['host' => 'https://idx.test', 'namespace' => 'ns1'],
            ],
        ], $app);
    }

    public function test_flush_sends_delete_all_request(): void
    {
        $http = new MockHttpClient;
        $http->responses[] = new Response(200, [], '{}');
        $transport = PineconeTestFactories::transport($http);

        $this->app->afterResolving(PineconeClientFactory::class, function (PineconeClientFactory $f) use ($transport): void {
            $f->setTransport($transport);
        });

        $this->artisan('pinecone:flush', ['--force' => true])
            ->assertExitCode(0);

        $this->assertCount(1, $http->requests);
        $body = json_decode((string) $http->requests[0]->getBody(), true);
        $this->assertTrue($body['deleteAll'] ?? false);
    }
}
