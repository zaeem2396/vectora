<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Core\VectorStore;

use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Core\VectorStore\Support\MetadataFilterEvaluator;

final class MetadataFilterEvaluatorTest extends TestCase
{
    public function test_null_filter_matches(): void
    {
        $this->assertTrue(MetadataFilterEvaluator::matches(['a' => 1], null));
    }

    public function test_eq_matches(): void
    {
        $f = ['region' => ['$eq' => 'eu']];
        $this->assertTrue(MetadataFilterEvaluator::matches(['region' => 'eu'], $f));
        $this->assertFalse(MetadataFilterEvaluator::matches(['region' => 'us'], $f));
    }

    public function test_and_combines(): void
    {
        $f = ['$and' => [
            ['a' => ['$eq' => 1]],
            ['b' => ['$eq' => 'x']],
        ]];
        $this->assertTrue(MetadataFilterEvaluator::matches(['a' => 1, 'b' => 'x'], $f));
        $this->assertFalse(MetadataFilterEvaluator::matches(['a' => 1, 'b' => 'y'], $f));
    }
}
