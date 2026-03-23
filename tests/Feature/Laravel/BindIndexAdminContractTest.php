<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Laravel;

use Vectora\Pinecone\Contracts\IndexAdminContract;
use Vectora\Pinecone\Core\Pinecone\PineconeIndexAdmin;

final class BindIndexAdminContractTest extends PineconeFeatureTestCase
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

    public function test_resolves_pinecone_index_admin(): void
    {
        $admin = $this->app->make(IndexAdminContract::class);
        $this->assertInstanceOf(PineconeIndexAdmin::class, $admin);
    }
}
