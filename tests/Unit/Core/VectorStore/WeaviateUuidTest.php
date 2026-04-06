<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Core\VectorStore;

use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Core\VectorStore\Support\WeaviateUuid;

final class WeaviateUuidTest extends TestCase
{
    public function test_deterministic_per_namespace_and_id(): void
    {
        $a = WeaviateUuid::fromNamespaceAndId('ns', '7');
        $b = WeaviateUuid::fromNamespaceAndId('ns', '7');
        $c = WeaviateUuid::fromNamespaceAndId('ns', '8');
        $this->assertSame($a, $b);
        $this->assertNotSame($a, $c);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $a
        );
    }
}
