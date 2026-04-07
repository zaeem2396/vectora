<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Search;

use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\DTO\QueryVectorMatch;
use Vectora\Pinecone\Search\FacetAggregator;

final class FacetAggregatorTest extends TestCase
{
    public function test_counts_per_metadata_key(): void
    {
        $m = [
            new QueryVectorMatch('a', 1.0, null, ['cat' => 'x']),
            new QueryVectorMatch('b', 0.9, null, ['cat' => 'x']),
            new QueryVectorMatch('c', 0.8, null, ['cat' => 'y']),
        ];
        $f = FacetAggregator::aggregate($m, ['cat']);
        $this->assertSame(2, $f['cat']['x']);
        $this->assertSame(1, $f['cat']['y']);
    }
}
