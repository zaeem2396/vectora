<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Eloquent;

use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Eloquent\SemanticFilter;

final class SemanticFilterMergeTest extends TestCase
{
    public function test_returns_extra_when_base_empty(): void
    {
        $f = ['a' => 1];
        $this->assertSame($f, SemanticFilter::merge([], $f));
        $this->assertSame($f, SemanticFilter::merge(null, $f));
    }

    public function test_returns_base_when_extra_empty(): void
    {
        $b = ['b' => 2];
        $this->assertSame($b, SemanticFilter::merge($b, []));
        $this->assertSame($b, SemanticFilter::merge($b, null));
    }

    public function test_combines_with_and(): void
    {
        $this->assertSame(
            ['$and' => [['x' => 1], ['y' => 2]]],
            SemanticFilter::merge(['x' => 1], ['y' => 2])
        );
    }
}
