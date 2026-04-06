<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Contracts;

use Vectora\Pinecone\DTO\RagSourceChunk;

/** Retrieves text chunks for a user query (e.g. vector search + hydration). */
interface RagRetrieverContract
{
    /**
     * @param  array<string, mixed>|null  $additionalFilter  Pinecone-style metadata filter
     * @return list<RagSourceChunk>
     */
    public function retrieve(string $query, int $topK = 5, ?array $additionalFilter = null): array;
}
