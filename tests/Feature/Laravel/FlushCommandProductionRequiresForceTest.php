<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Laravel;

final class FlushCommandProductionRequiresForceTest extends PineconeFeatureTestCase
{
    protected function defineEnvironment($app): void
    {
        $app['env'] = 'production';
        $app['config']->set('app.env', 'production');
        $this->mergePineconeConfig([
            'api_key' => 'test-key',
            'indexes' => [
                'default' => ['host' => 'https://idx.test', 'namespace' => ''],
            ],
        ], $app);
    }

    public function test_refuses_without_force_in_production(): void
    {
        $this->artisan('pinecone:flush')
            ->assertExitCode(1);
    }
}
