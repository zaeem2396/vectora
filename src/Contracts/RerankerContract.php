<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Contracts;

use Vectora\Pinecone\DTO\QueryVectorMatch;

/** Reorders or rescales vector query hits using query context (Phase 10). */
interface RerankerContract
{
    /**
     * @param  list<QueryVectorMatch>  $matches
     * @return list<QueryVectorMatch>
     */
    public function rerank(array $matches, string $queryText): array;
}
