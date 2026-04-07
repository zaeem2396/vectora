<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Search;

use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\DTO\QueryVectorMatch;
use Vectora\Pinecone\Search\ScoreNormalizer;

final class ScoreNormalizerTest extends TestCase
{
    public function test_min_max_scales_to_unit_interval(): void
    {
        $m = [
            new QueryVectorMatch('a', 0.0, null, null),
            new QueryVectorMatch('b', 0.5, null, null),
            new QueryVectorMatch('c', 1.0, null, null),
        ];
        $out = ScoreNormalizer::minMax($m);
        $this->assertSame(0.0, $out[0]->score);
        $this->assertSame(1.0, $out[2]->score);
    }
}
