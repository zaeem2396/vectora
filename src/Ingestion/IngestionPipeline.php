<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Ingestion;

use Vectora\Pinecone\Contracts\TextChunkingStrategy;
use Vectora\Pinecone\DTO\IngestedChunk;

/**
 * Applies chunking and optional metadata enrichment to raw document text.
 *
 * @phpstan-type Enricher callable(IngestedChunk): IngestedChunk
 */
final class IngestionPipeline
{
    /**
     * @param  callable(IngestedChunk): IngestedChunk|null  $enrich
     * @param  array<string, mixed>  $baseMetadata
     * @return list<IngestedChunk>
     */
    public function run(
        string $plainText,
        TextChunkingStrategy $chunker,
        ?callable $enrich = null,
        array $baseMetadata = [],
    ): array {
        $segments = $chunker->chunk($plainText);
        $out = [];
        foreach ($segments as $i => $segment) {
            $chunk = new IngestedChunk($segment, $i, array_merge($baseMetadata, ['chunk_index' => $i]));
            if ($enrich !== null) {
                $chunk = $enrich($chunk);
            }
            $out[] = $chunk;
        }

        return $out;
    }
}
