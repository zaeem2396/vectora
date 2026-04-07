<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Search;

use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\DTO\QueryVectorMatch;
use Vectora\Pinecone\Search\OffsetPagination;

final class OffsetPaginationTest extends TestCase
{
    public function test_slice_preserves_total(): void
    {
        $m = [
            new QueryVectorMatch('1', 1.0, null, null),
            new QueryVectorMatch('2', 0.5, null, null),
        ];
        [$slice, $total] = OffsetPagination::slice($m, 1, 1);
        $this->assertSame(2, $total);
        $this->assertCount(1, $slice);
        $this->assertSame('2', $slice[0]->id);
    }
}
