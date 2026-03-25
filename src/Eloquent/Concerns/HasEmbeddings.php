<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Vectora\Pinecone\Contracts\Embeddable;
use Vectora\Pinecone\Contracts\EmbeddingDriver;
use Vectora\Pinecone\DTO\DeleteVectorsRequest;
use Vectora\Pinecone\DTO\QueryVectorsRequest;
use Vectora\Pinecone\DTO\QueryVectorsResult;
use Vectora\Pinecone\DTO\UpsertVectorsRequest;
use Vectora\Pinecone\DTO\VectorRecord;
use Vectora\Pinecone\Eloquent\SemanticFilter;
use Vectora\Pinecone\Laravel\Jobs\DeleteVectorsJob;
use Vectora\Pinecone\Laravel\Jobs\SyncModelEmbeddingJob;
use Vectora\Pinecone\Laravel\PineconeClientFactory;

/**
 * @phpstan-require-implements Embeddable
 *
 * @mixin Model
 */
trait HasEmbeddings
{
    use HandlesVectorEmbeddingBatch;

    public static function bootHasEmbeddings(): void
    {
        static::created(static function (Model $model): void {
            if (! $model instanceof Embeddable) {
                return;
            }
            $model->queueVectorEmbeddingUpsert();
        });

        static::updated(static function (Model $model): void {
            if (! $model instanceof Embeddable) {
                return;
            }
            if (! $model->embeddingRelevantAttributesChanged()) {
                return;
            }
            $model->queueVectorEmbeddingUpsert();
        });

        static::deleted(static function (Model $model): void {
            if (! $model instanceof Embeddable) {
                return;
            }
            $model->queueVectorEmbeddingDelete();
        });

        call_user_func([static::class, 'restored'], static function (Model $model): void {
            if (! $model instanceof Embeddable) {
                return;
            }
            $model->queueVectorEmbeddingUpsert();
        });
    }

    /**
     * @return list<string>
     */
    abstract public static function vectorEmbeddingFields(): array;

    public function vectorEmbeddingText(): string
    {
        $parts = [];
        foreach (static::vectorEmbeddingFields() as $field) {
            $parts[] = (string) ($this->getAttribute($field) ?? '');
        }

        return trim(implode("\n", $parts));
    }

    /**
     * @return array<string, mixed>
     */
    public function vectorEmbeddingMetadata(): array
    {
        return [
            'vectora_model' => static::class,
            'vectora_key' => $this->getKey(),
        ];
    }

    public function vectorEmbeddingId(): string
    {
        return (string) $this->getKey();
    }

    public static function vectorEmbeddingIndex(): ?string
    {
        return null;
    }

    public static function vectorEmbeddingNamespace(): ?string
    {
        return null;
    }

    public static function vectorEmbeddingSyncMode(): string
    {
        $mode = config('pinecone.eloquent.default_sync', 'queued');
        $mode = is_string($mode) ? strtolower($mode) : 'queued';

        return in_array($mode, ['sync', 'queued'], true) ? $mode : 'queued';
    }

    public function shouldSyncVectorEmbedding(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function semanticSearchMetadataFilter(): ?array
    {
        return ['vectora_model' => static::class];
    }

    public function syncVectorEmbeddingNow(): void
    {
        if (! $this->shouldSyncVectorEmbedding()) {
            return;
        }

        $text = $this->vectorEmbeddingText();
        if ($text === '') {
            $this->deleteVectorEmbeddingNow();

            return;
        }

        $driver = app(EmbeddingDriver::class);
        $vector = $driver->embed($text);
        $factory = app(PineconeClientFactory::class);
        $store = $factory->vectorStore(static::vectorEmbeddingIndex());
        $record = new VectorRecord(
            $this->vectorEmbeddingId(),
            $vector,
            $this->vectorEmbeddingMetadata(),
        );
        $store->upsert(new UpsertVectorsRequest([$record], static::vectorEmbeddingNamespace()));
    }

    public function deleteVectorEmbeddingNow(): void
    {
        $factory = app(PineconeClientFactory::class);
        $store = $factory->vectorStore(static::vectorEmbeddingIndex());
        $store->delete(new DeleteVectorsRequest(
            namespace: static::vectorEmbeddingNamespace(),
            ids: [$this->vectorEmbeddingId()],
            filter: null,
            deleteAll: false,
        ));
    }

    /**
     * Run a semantic query scoped to this model type (when {@see semanticSearchMetadataFilter()} is non-null).
     *
     * @param  array<string, mixed>|null  $additionalFilter
     */
    public static function semanticSearch(string $query, int $topK = 10, ?array $additionalFilter = null): QueryVectorsResult
    {
        if ($topK < 1) {
            throw new \InvalidArgumentException('topK must be at least 1.');
        }

        $driver = app(EmbeddingDriver::class);
        $vector = $driver->embed($query);
        $filter = SemanticFilter::merge(static::semanticSearchMetadataFilter(), $additionalFilter);
        $factory = app(PineconeClientFactory::class);
        $store = $factory->vectorStore(static::vectorEmbeddingIndex());

        return $store->query(new QueryVectorsRequest(
            vector: $vector,
            topK: $topK,
            namespace: static::vectorEmbeddingNamespace(),
            filter: $filter,
            includeMetadata: true,
            includeValues: false,
            queryByVectorId: null,
        ));
    }

    /**
     * @param  array<string, mixed>|null  $additionalFilter
     * @return Collection<int, static>
     */
    public static function semanticSearchModels(string $query, int $topK = 10, ?array $additionalFilter = null): Collection
    {
        $result = static::semanticSearch($query, $topK, $additionalFilter);
        $matches = $result->matches;
        if ($matches === []) {
            return collect();
        }

        $ids = [];
        foreach ($matches as $m) {
            $ids[] = $m->id;
        }

        $keyName = (static::query()->getModel())->getKeyName();

        /** @var Collection<int, static> $found */
        $found = static::query()->whereIn($keyName, $ids)->get()->keyBy(
            static fn (Model $row): string => (string) $row->getKey()
        );

        $ordered = collect();
        foreach ($ids as $id) {
            $row = $found->get((string) $id);
            if ($row !== null) {
                $ordered->push($row);
            }
        }

        return $ordered;
    }

    public function queueVectorEmbeddingUpsert(): void
    {
        if (! $this->shouldSyncVectorEmbedding()) {
            return;
        }

        if (static::vectorEmbeddingSyncMode() === 'queued') {
            SyncModelEmbeddingJob::dispatch($this);
        } else {
            $this->syncVectorEmbeddingNow();
        }
    }

    public function queueVectorEmbeddingDelete(): void
    {
        if (static::vectorEmbeddingSyncMode() === 'queued') {
            DeleteVectorsJob::dispatch(
                namespace: static::vectorEmbeddingNamespace(),
                ids: [$this->vectorEmbeddingId()],
                filter: null,
                deleteAll: false,
                index: static::vectorEmbeddingIndex(),
            );
        } else {
            $this->deleteVectorEmbeddingNow();
        }
    }

    public function embeddingRelevantAttributesChanged(): bool
    {
        foreach (static::vectorEmbeddingFields() as $field) {
            if ($this->wasChanged($field)) {
                return true;
            }
        }

        return false;
    }
}
