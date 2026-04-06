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

        return $this->chunksFromMatches($class, $result->matches);
    }

    /**
     * @param  class-string<Model&Embeddable>  $class
     * @param  list<QueryVectorMatch>  $matches
     * @return list<RagSourceChunk>
     */
    private function chunksFromMatches(string $class, array $matches): array
    {
        if ($matches === []) {
            return [];
        }

        $lookupKeys = [];
        foreach ($matches as $m) {
            $lookupKeys[] = $this->lookupKeyForMatch($m);
        }

        $keyName = ($class::query()->getModel())->getKeyName();
        $uniqueKeys = array_values(array_unique($lookupKeys));

        $found = $class::query()->whereIn($keyName, $uniqueKeys)->get()->keyBy(
            static fn (Model $row): string => (string) $row->getKey()
        );

        $chunks = [];
        foreach ($matches as $i => $m) {
            $meta = is_array($m->metadata) ? $m->metadata : [];
            $key = $lookupKeys[$i];
            $row = $found->get((string) $key);
            $text = '';
            if ($row instanceof Embeddable) {
                $t = trim($row->vectorEmbeddingText());
                $text = $t !== '' ? $t : $this->fallbackSnippet($meta);
            } else {
                $text = $this->fallbackSnippet($meta);
            }
            $chunks[] = new RagSourceChunk($m->id, $text, $m->score, $meta);
        }

        return $chunks;
    }

    private function lookupKeyForMatch(QueryVectorMatch $m): string
    {
        $meta = $m->metadata ?? [];
        if (isset($meta['vectora_key'])) {
            return (string) $meta['vectora_key'];
        }

        return (string) $m->id;
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
