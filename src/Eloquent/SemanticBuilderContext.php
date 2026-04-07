<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Eloquent;

/**
 * Tracks the last semantic vector query applied to an Eloquent builder so
 * {@see SemanticEloquentBuilder::semanticOrderBy()} can reuse work when parameters match.
 */
final class SemanticBuilderContext
{
    /**
     * @param  list<string|int>  $orderedIds  Vector ids in descending similarity order
     * @param  array<string, mixed>|null  $additionalFilter
     */
    public function __construct(
        public readonly string $queryText,
        public readonly int $topK,
        public readonly ?array $additionalFilter,
        public readonly array $orderedIds,
    ) {}

    /**
     * @param  array<string, mixed>|null  $additionalFilter
     */
    public function matches(string $queryText, int $topK, ?array $additionalFilter): bool
    {
        return $this->queryText === $queryText
            && $this->topK === $topK
            && $this->normalizeFilter($this->additionalFilter) === $this->normalizeFilter($additionalFilter);
    }

    /**
     * @param  array<string, mixed>|null  $filter
     */
    private function normalizeFilter(?array $filter): string
    {
        if ($filter === null) {
            return '';
        }

        $copy = $filter;
        ksort($copy);

        return json_encode($copy, JSON_THROW_ON_ERROR);
    }
}
