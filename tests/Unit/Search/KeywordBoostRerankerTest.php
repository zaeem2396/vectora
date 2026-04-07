<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Search;

use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\DTO\QueryVectorMatch;
use Vectora\Pinecone\Search\KeywordBoostReranker;

final class KeywordBoostRerankerTest extends TestCase
{
    public function test_boosts_when_keyword_in_metadata(): void
    {
        $r = new KeywordBoostReranker('text', 1.0, []);
        $matches = [
            new QueryVectorMatch('a', 0.1, null, ['text' => 'foo bar']),
            new QueryVectorMatch('b', 0.9, null, ['text' => 'zzz']),
        ];
        $out = $r->rerank($matches, 'foo');
        $this->assertSame('a', $out[0]->id);
    }
}
