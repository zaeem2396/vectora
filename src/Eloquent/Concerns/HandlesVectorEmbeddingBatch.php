<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Vectora\Pinecone\Contracts\Embeddable;
use Vectora\Pinecone\Contracts\EmbeddingDriver;
use Vectora\Pinecone\DTO\UpsertVectorsRequest;
use Vectora\Pinecone\DTO\VectorRecord;
use Vectora\Pinecone\Laravel\VectorStoreManager;

/**
 * @phpstan-require-implements Embeddable
 *
 * @mixin Model
 */
trait HandlesVectorEmbeddingBatch
{
    /**
     * @param  iterable<int, Model&Embeddable>|Collection<int, Model&Embeddable>  $models
     */
    public static function upsertVectorEmbeddingsForModels(iterable $models): void
    {
        $col = $models instanceof Collection ? $models : collect(iterator_to_array($models, false));
        if ($col->isEmpty()) {
            return;
        }

        $mode = static::vectorEmbeddingSyncMode();
        if ($mode === 'queued') {
            foreach ($col as $model) {
                /** @var Model&Embeddable $model */
                if ($model instanceof Embeddable) {
                    $model->queueVectorEmbeddingUpsert();
                }
            }

            return;
        }

        static::upsertVectorEmbeddingsForModelsSynchronously($col);
    }

    /**
     * @param  \Closure(Builder<static>): Builder<static>|null  $scope
     */
    public static function reindexAllEmbeddings(int $chunkSize = 100, ?\Closure $scope = null): int
    {
        if ($chunkSize < 1) {
            throw new \InvalidArgumentException('chunkSize must be at least 1.');
        }

        $q = static::query();
        if ($scope !== null) {
            $q = $scope($q);
        }

        $processed = 0;
        $q->chunkById($chunkSize, function (Collection $chunk) use (&$processed): void {
            static::upsertVectorEmbeddingsForModels($chunk);
            $processed += $chunk->count();
        });

        return $processed;
    }

    /**
     * @param  Collection<int, Model&Embeddable>  $models
     */
    protected static function upsertVectorEmbeddingsForModelsSynchronously(Collection $models): void
    {
        $driver = app(EmbeddingDriver::class);
        $store = app(VectorStoreManager::class)->forModel(static::class);

        /** @var list<Embeddable&Model> $pending */
        $pending = [];
        $texts = [];

        foreach ($models as $model) {
            if (! $model instanceof Embeddable) {
                continue;
            }
            if (! $model->shouldSyncVectorEmbedding()) {
                continue;
            }

            $text = $model->vectorEmbeddingText();
            if ($text === '') {
                $model->deleteVectorEmbeddingNow();

                continue;
            }

            $pending[] = $model;
            $texts[] = $text;
        }

        if ($texts === []) {
            return;
        }

        $vectors = $driver->embedMany($texts);
        $records = [];
        foreach ($pending as $i => $model) {
            $vec = $vectors[$i] ?? null;
            if (! is_array($vec)) {
                continue;
            }
            $records[] = new VectorRecord(
                $model->vectorEmbeddingId(),
                $vec,
                $model->vectorEmbeddingMetadata(),
            );
        }

        if ($records === []) {
            return;
        }

        $store->upsert(new UpsertVectorsRequest($records, static::vectorEmbeddingNamespace()));
    }
}
