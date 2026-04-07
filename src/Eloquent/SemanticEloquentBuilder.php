<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Eloquent;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Vectora\Pinecone\Contracts\Embeddable;
use Vectora\Pinecone\Laravel\Exceptions\SemanticSearchInvalidArgumentException;

/**
 * Eloquent query builder with Phase 11 semantic constraints: {@see semanticWhere()} and
 * {@see semanticOrderBy()} compose with normal SQL where/order clauses.
 *
 * @extends Builder<Model>
 */
class SemanticEloquentBuilder extends Builder
{
    private ?SemanticBuilderContext $semanticContext = null;

    /**
     * Restrict rows to vector matches for the given natural-language query (same pipeline as
     * {@see Embeddable::semanticSearch()}). Results are ordered by descending similarity when possible.
     *
     * @param  array<string, mixed>|null  $additionalFilter  Merged with the model metadata filter
     */
    public function semanticWhere(string $queryText, int $topK = 10, ?array $additionalFilter = null): static
    {
        if ($topK < 1) {
            throw SemanticSearchInvalidArgumentException::topKTooLow();
        }

        $model = $this->ensureEmbeddableModel();
        $result = $model::semanticSearch($queryText, $topK, $additionalFilter);
        $orderedIds = [];
        foreach ($result->matches as $m) {
            $orderedIds[] = $m->id;
        }

        $this->semanticContext = new SemanticBuilderContext($queryText, $topK, $additionalFilter, $orderedIds);

        $key = $model->getKeyName();
        if ($orderedIds === []) {
            $this->whereRaw('0 = 1');

            return $this;
        }

        $this->whereIn($key, $orderedIds);
        $this->applySimilarityOrdering($orderedIds);

        return $this;
    }

    /**
     * Order rows by vector similarity to the query. If the builder has no prior semantic constraint
     * with the same parameters, this runs a vector query and restricts to the top matches (same as
     * {@see semanticWhere()}). If a matching {@see semanticWhere()} was already applied, only
     * re-applies ordering (useful after further constraints in rare cases).
     *
     * @param  array<string, mixed>|null  $additionalFilter
     */
    public function semanticOrderBy(string $queryText, int $topK = 10, ?array $additionalFilter = null): static
    {
        if ($topK < 1) {
            throw SemanticSearchInvalidArgumentException::topKTooLow();
        }

        if ($this->semanticContext !== null && $this->semanticContext->matches($queryText, $topK, $additionalFilter)) {
            $this->reorder();
            $this->applySimilarityOrdering($this->semanticContext->orderedIds);

            return $this;
        }

        return $this->semanticWhere($queryText, $topK, $additionalFilter);
    }

    /**
     * @param  list<string|int>  $orderedIds
     */
    private function applySimilarityOrdering(array $orderedIds): void
    {
        if ($orderedIds === []) {
            return;
        }

        $model = $this->ensureEmbeddableModel();
        $key = $model->getQualifiedKeyName();
        $conn = $this->query->getConnection();
        $driver = $conn instanceof Connection ? $conn->getDriverName() : 'sqlite';

        if ($driver === 'mysql') {
            $placeholders = implode(',', array_fill(0, count($orderedIds), '?'));
            $this->orderByRaw('FIELD('.$key.', '.$placeholders.')', $orderedIds);

            return;
        }

        $cases = [];
        $bindings = [];
        foreach ($orderedIds as $rank => $id) {
            $cases[] = 'WHEN '.$key.' = ? THEN '.(int) $rank;
            $bindings[] = $id;
        }
        $else = count($orderedIds);
        $this->orderByRaw('CASE '.implode(' ', $cases).' ELSE '.$else.' END', $bindings);
    }

    /**
     * @return Model&Embeddable
     */
    private function ensureEmbeddableModel(): Model
    {
        $model = $this->model;
        if (! $model instanceof Embeddable) {
            throw SemanticSearchInvalidArgumentException::modelMustImplementEmbeddable($model::class);
        }

        /** @var Model&Embeddable $model */
        return $model;
    }
}
