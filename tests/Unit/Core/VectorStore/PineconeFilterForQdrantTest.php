<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Core\VectorStore;

use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Core\Http\Json;
use Vectora\Pinecone\Core\VectorStore\Support\PineconeFilterForQdrant;

final class PineconeFilterForQdrantTest extends TestCase
{
    public function test_eq_maps_to_match(): void
    {
        $out = PineconeFilterForQdrant::convert(['t' => ['$eq' => 'hello']]);
        $this->assertIsArray($out);
        $this->assertArrayHasKey('must', $out);
    }

    public function test_null_returns_null(): void
    {
        $this->assertNull(PineconeFilterForQdrant::convert(null));
    }

    public function test_field_and_or_combine_in_must(): void
    {
        $out = PineconeFilterForQdrant::convert([
            'status' => ['$eq' => 'active'],
            '$or' => [
                ['role' => ['$eq' => 'admin']],
                ['role' => ['$eq' => 'mod']],
            ],
        ]);
        $this->assertIsArray($out);
        $this->assertArrayHasKey('must', $out);
        $must = $out['must'];
        $this->assertIsArray($must);
        $this->assertGreaterThanOrEqual(2, count($must));
        $json = Json::encode($out);
        $this->assertStringContainsString('status', $json);
        $this->assertStringContainsString('should', $json);
        $this->assertStringContainsString('minimum_should_match', $json);
    }
}
