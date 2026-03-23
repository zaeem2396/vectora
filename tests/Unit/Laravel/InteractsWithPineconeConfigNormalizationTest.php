<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Laravel;

use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Laravel\Support\InteractsWithPineconeConfig;

final class InteractsWithPineconeConfigNormalizationTest extends TestCase
{
    public function test_maps_legacy_host_when_indexes_empty(): void
    {
        $t = new class
        {
            use InteractsWithPineconeConfig;

            /** @param  array<string, mixed>  $c */
            public function r(array $c): array
            {
                return $this->resolveIndexes($c);
            }
        };

        $out = $t->r([
            'indexes' => [],
            'host' => 'https://legacy',
            'namespace' => 'n',
        ]);

        $this->assertSame('https://legacy', $out['default']['host']);
        $this->assertSame('n', $out['default']['namespace']);
    }

    public function test_merges_root_host_when_package_indexes_default_host_is_empty(): void
    {
        $t = new class
        {
            use InteractsWithPineconeConfig;

            /** @param  array<string, mixed>  $c */
            public function r(array $c): array
            {
                return $this->resolveIndexes($c);
            }
        };

        $out = $t->r([
            'default' => 'default',
            'host' => 'https://published-only',
            'namespace' => 'legacy-ns',
            'indexes' => [
                'default' => [
                    'host' => '',
                    'namespace' => '',
                ],
            ],
        ]);

        $this->assertSame('https://published-only', $out['default']['host']);
        $this->assertSame('legacy-ns', $out['default']['namespace']);
    }

    public function test_legacy_single_host_uses_configured_default_index_name(): void
    {
        $t = new class
        {
            use InteractsWithPineconeConfig;

            /** @param  array<string, mixed>  $c */
            public function r(array $c): array
            {
                return $this->resolveIndexes($c);
            }
        };

        $out = $t->r([
            'default' => 'primary',
            'indexes' => [],
            'host' => 'https://single.example',
            'namespace' => 'app',
        ]);

        $this->assertArrayHasKey('primary', $out);
        $this->assertSame('https://single.example', $out['primary']['host']);
        $this->assertSame('app', $out['primary']['namespace']);
    }
}
