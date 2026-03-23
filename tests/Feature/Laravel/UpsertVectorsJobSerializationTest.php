<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Laravel;

use Vectora\Pinecone\Laravel\Jobs\UpsertVectorsJob;

final class UpsertVectorsJobSerializationTest extends PineconeFeatureTestCase
{
    protected function defineEnvironment($app): void
    {
        $this->mergePineconeConfig([
            'api_key' => 'k',
            'indexes' => ['default' => ['host' => 'https://x', 'namespace' => '']],
        ], $app);
    }

    public function test_payload_survives_serialization(): void
    {
        $job = new UpsertVectorsJob(
            [['id' => 'z', 'values' => [0.5, 0.6], 'metadata' => ['t' => 1]]],
            'ns',
            'default'
        );
        $copy = unserialize(serialize($job));
        $this->assertSame('z', $copy->vectors[0]['id']);
        $this->assertSame('ns', $copy->namespace);
        $this->assertSame('default', $copy->index);
    }
}
