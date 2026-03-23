<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Laravel;

use Vectora\Pinecone\Laravel\Jobs\UpsertVectorsJob;

final class JobsRespectQueueConfigTest extends PineconeFeatureTestCase
{
    protected function defineEnvironment($app): void
    {
        $this->mergePineconeConfig([
            'api_key' => 'k',
            'indexes' => ['default' => ['host' => 'https://x', 'namespace' => '']],
            'queue' => [
                'connection' => 'redis',
                'queue' => 'vectors',
            ],
        ], $app);
    }

    public function test_upsert_job_applies_queue_settings(): void
    {
        $job = new UpsertVectorsJob([['id' => 'a', 'values' => [0.1]]]);
        $this->assertSame('redis', $job->connection);
        $this->assertSame('vectors', $job->queue);
    }
}
