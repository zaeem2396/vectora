<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel\Rag;

use Illuminate\Database\Eloquent\Model;
use Vectora\Pinecone\Contracts\Embeddable;
use Vectora\Pinecone\Contracts\RagRetrieverContract;
use Vectora\Pinecone\DTO\QueryVectorMatch;
use Vectora\Pinecone\DTO\RagSourceChunk;

/**
 * Vector retrieval scoped to one {@see Embeddable} model class (same filters as semantic search).
 */
final class EmbeddableRagRetriever implements RagRetrieverContract
{
    /**
     * @param  class-string<Model&Embeddable>  $modelClass
     */
    public function __construct(
        private readonly string $modelClass,
    ) {}

    public function retrieve(string $query, int $topK = 5, ?array $additionalFilter = null): array
    {
        /** @var class-string<Model&Embeddable> $class */
        $class = $this->modelClass;
        $result = $class::semanticSearch($query, $topK, $additionalFilter);
        $chunks = [];
        foreach ($result->matches as $m) {
            $chunks[] = $this->matchToChunk($m);
        }

        return $chunks;
    }

    private function matchToChunk(QueryVectorMatch $m): RagSourceChunk
    {
        $text = $this->resolveText($m);
        $meta = is_array($m->metadata) ? $m->metadata : [];

        return new RagSourceChunk($m->id, $text, $m->score, $meta);
    }

    private function resolveText(QueryVectorMatch $m): string
    {
        /** @var class-string<Model&Embeddable> $class */
        $class = $this->modelClass;
        $key = $m->id;
        $meta = $m->metadata ?? [];
        if (isset($meta['vectora_key'])) {
            $key = (string) $meta['vectora_key'];
        }

        $row = $class::query()->find($key);
        if ($row instanceof Embeddable) {
            $t = trim($row->vectorEmbeddingText());

            return $t !== '' ? $t : $this->fallbackSnippet($meta);
        }

        return $this->fallbackSnippet($meta);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function fallbackSnippet(array $meta): string
    {
        foreach (['text', 'snippet', 'content', 'body'] as $k) {
            if (isset($meta[$k]) && is_string($meta[$k]) && trim($meta[$k]) !== '') {
                return trim($meta[$k]);
            }
        }

        return '';
    }
}
