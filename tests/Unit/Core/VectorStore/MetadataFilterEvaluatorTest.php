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

    public function test_eq_null_matches_missing_or_null_metadata(): void
    {
        $f = ['optional' => ['$eq' => null]];
        $this->assertTrue(MetadataFilterEvaluator::matches([], $f));
        $this->assertTrue(MetadataFilterEvaluator::matches(['optional' => null], $f));
        $this->assertFalse(MetadataFilterEvaluator::matches(['optional' => ''], $f));
        $this->assertFalse(MetadataFilterEvaluator::matches(['optional' => 'x'], $f));
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

    public function test_or_with_sibling_field_requires_both(): void
    {
        $f = [
            'status' => ['$eq' => 'active'],
            '$or' => [
                ['role' => ['$eq' => 'admin']],
                ['role' => ['$eq' => 'mod']],
            ],
        ];
        $this->assertTrue(MetadataFilterEvaluator::matches(['status' => 'active', 'role' => 'admin'], $f));
        $this->assertTrue(MetadataFilterEvaluator::matches(['status' => 'active', 'role' => 'mod'], $f));
        $this->assertFalse(MetadataFilterEvaluator::matches(['status' => 'active', 'role' => 'user'], $f));
        $this->assertFalse(MetadataFilterEvaluator::matches(['status' => 'inactive', 'role' => 'admin'], $f));
    }

    public function test_and_with_sibling_field_requires_both(): void
    {
        $f = [
            '$and' => [
                ['a' => ['$eq' => 1]],
            ],
            'b' => ['$eq' => 'x'],
        ];
        $this->assertTrue(MetadataFilterEvaluator::matches(['a' => 1, 'b' => 'x'], $f));
        $this->assertFalse(MetadataFilterEvaluator::matches(['a' => 1, 'b' => 'y'], $f));
        $this->assertFalse(MetadataFilterEvaluator::matches(['a' => 2, 'b' => 'x'], $f));
    }

    public function test_empty_or_is_false(): void
    {
        $f = ['$or' => []];
        $this->assertFalse(MetadataFilterEvaluator::matches(['a' => 1], $f));
    }
}
