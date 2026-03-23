<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature;

use Orchestra\Testbench\TestCase;
use Vectora\Pinecone\Laravel\PineconeServiceProvider;

final class PackageLoadsTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [PineconeServiceProvider::class];
    }

    public function test_config_is_merged(): void
    {
        $this->assertIsArray(config('pinecone'));
        $this->assertArrayHasKey('api_key', config('pinecone'));
        $this->assertArrayHasKey('api_version', config('pinecone'));
        $this->assertArrayHasKey('indexes', config('pinecone'));
        $this->assertArrayHasKey('queue', config('pinecone'));
    }

    public function test_vectora_pinecone_is_bound(): void
    {
        $this->assertTrue($this->app->bound('vectora.pinecone'));
    }
}
