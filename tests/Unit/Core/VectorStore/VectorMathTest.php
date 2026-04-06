<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Core\VectorStore;

use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Core\VectorStore\Support\VectorMath;

final class VectorMathTest extends TestCase
{
    public function test_cosine_of_parallel_vectors_is_one(): void
    {
        $a = [1.0, 0.0, 0.0];
        $b = [2.0, 0.0, 0.0];
        $this->assertEqualsWithDelta(1.0, VectorMath::cosineSimilarity($a, $b), 1e-6);
    }

    public function test_cosine_of_orthogonal_vectors_is_zero(): void
    {
        $a = [1.0, 0.0];
        $b = [0.0, 1.0];
        $this->assertEqualsWithDelta(0.0, VectorMath::cosineSimilarity($a, $b), 1e-6);
    }
}
