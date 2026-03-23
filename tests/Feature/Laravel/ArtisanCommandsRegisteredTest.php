<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Laravel;

use Illuminate\Support\Facades\Artisan;

final class ArtisanCommandsRegisteredTest extends PineconeFeatureTestCase
{
    protected function defineEnvironment($app): void
    {
        $this->mergePineconeConfig([
            'api_key' => 'k',
            'indexes' => ['default' => ['host' => 'https://x', 'namespace' => '']],
        ], $app);
    }

    public function test_pinecone_commands_exist(): void
    {
        $all = Artisan::all();
        $this->assertArrayHasKey('pinecone:flush', $all);
        $this->assertArrayHasKey('pinecone:sync', $all);
    }
}
