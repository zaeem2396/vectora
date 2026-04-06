<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Contracts;

/**
 * Splits document text into smaller strings for embedding (Phase 9 ingestion).
 */
interface TextChunkingStrategy
{
    /**
     * @return list<string> Non-empty text segments in order.
     */
    public function chunk(string $text): array;
}
