<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Laravel;

use Vectora\Pinecone\Contracts\EmbeddingDriver;

final class BindEmbeddingDriverContractTest extends PineconeFeatureTestCase
{
    public function test_embedding_driver_contract_resolves(): void
    {
        $this->mergePineconeConfig([
            'embeddings' => [
                'default' => 'deterministic',
                'cache' => ['enabled' => false],
            ],
        ]);

        $d = $this->app->make(EmbeddingDriver::class);
        $this->assertInstanceOf(EmbeddingDriver::class, $d);
        $this->assertCount(8, $d->embed('hello'));
    }
}
