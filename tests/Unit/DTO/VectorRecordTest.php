<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\DTO;

use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\DTO\VectorRecord;

final class VectorRecordTest extends TestCase
{
    public function test_to_api_array_includes_metadata_when_set(): void
    {
        $v = new VectorRecord('a', [0.1, 0.2], ['k' => 'v']);
        $this->assertSame([
            'id' => 'a',
            'values' => [0.1, 0.2],
            'metadata' => ['k' => 'v'],
        ], $v->toApiArray());
    }

    public function test_to_api_array_omits_empty_metadata(): void
    {
        $v = new VectorRecord('a', [1.0], []);
        $this->assertArrayNotHasKey('metadata', $v->toApiArray());
    }
}
