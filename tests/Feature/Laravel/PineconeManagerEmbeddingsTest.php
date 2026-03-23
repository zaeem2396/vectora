<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Laravel;

use Vectora\Pinecone\Laravel\Facades\Pinecone;

final class PineconeManagerEmbeddingsTest extends PineconeFeatureTestCase
{
    public function test_manager_embed_and_embed_many(): void
    {
        $this->mergePineconeConfig([
            'embeddings' => [
                'default' => 'deterministic',
                'drivers' => [
                    'deterministic' => ['dimensions' => 6],
                ],
                'cache' => ['enabled' => false],
            ],
        ]);

        $this->assertCount(6, Pinecone::embed('x'));
        $batch = Pinecone::embedMany(['a', 'b']);
        $this->assertCount(2, $batch);
        $this->assertCount(6, $batch[0]);
    }

    public function test_named_openai_driver_requires_key(): void
    {
        $this->mergePineconeConfig([
            'embeddings' => [
                'default' => 'deterministic',
                'drivers' => [
                    'openai' => [
                        'api_key' => '',
                        'model' => 'm',
                    ],
                ],
                'cache' => ['enabled' => false],
            ],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        Pinecone::embeddings('openai');
    }

    public function test_unknown_driver_throws(): void
    {
        $this->mergePineconeConfig([
            'embeddings' => [
                'default' => 'deterministic',
                'cache' => ['enabled' => false],
            ],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        Pinecone::embeddings('no-such-driver');
    }
}
