<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Laravel;

use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase;
use Vectora\Pinecone\Laravel\Facades\Pinecone;
use Vectora\Pinecone\Laravel\PineconeServiceProvider;

abstract class PineconeFeatureTestCase extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function mergePineconeConfig(array $overrides, ?Application $app = null): void
    {
        $application = $app ?? $this->app;
        /** @var array<string, mixed> $base */
        $base = require dirname(__DIR__, 3).'/config/pinecone.php';
        $application['config']->set('pinecone', array_replace_recursive($base, $overrides));
    }

    protected function getPackageProviders($app): array
    {
        return [PineconeServiceProvider::class];
    }

    /**
     * @return array<string, class-string>
     */
    protected function getPackageAliases($app): array
    {
        return [
            'Pinecone' => Pinecone::class,
        ];
    }
}
