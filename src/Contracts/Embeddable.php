<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Contracts;

use Vectora\Pinecone\Eloquent\Concerns\HasEmbeddings;
use Vectora\Pinecone\Laravel\Jobs\SyncModelEmbeddingJob;

/**
 * Eloquent models that sync text embeddings to a vector index via {@see HasEmbeddings}.
 */
interface Embeddable
{
    /**
     * Attribute names used to build embedding text and to detect changes on update.
     *
     * @return list<string>
     */
    public static function vectorEmbeddingFields(): array;

    public function vectorEmbeddingText(): string;

    /**
     * @return array<string, mixed>
     */
    public function vectorEmbeddingMetadata(): array;

    public function vectorEmbeddingId(): string;

    /** Logical Pinecone index connection name; null uses the default index. */
    public static function vectorEmbeddingIndex(): ?string;

    /**
     * Optional `pinecone.vector_store.drivers` key; null uses `vector_store.default`.
     * When not `pinecone`, {@see vectorEmbeddingIndex()} is ignored for the vector store (still used for jobs metadata).
     */
    public static function vectorEmbeddingStoreDriver(): ?string;

    /** Namespace segment for upsert/query/delete; null uses the connection default. */
    public static function vectorEmbeddingNamespace(): ?string;

    /** `sync` runs inline; `queued` uses {@see SyncModelEmbeddingJob}. */
    public static function vectorEmbeddingSyncMode(): string;

    public function shouldSyncVectorEmbedding(): bool;

    public function syncVectorEmbeddingNow(): void;

    public function deleteVectorEmbeddingNow(): void;

    /** Queue or run an upsert according to {@see vectorEmbeddingSyncMode()}. */
    public function queueVectorEmbeddingUpsert(): void;

    /** Queue or run a vector delete according to {@see vectorEmbeddingSyncMode()}. */
    public function queueVectorEmbeddingDelete(): void;

    /** True when any {@see vectorEmbeddingFields()} attribute changed on the last save. */
    public function embeddingRelevantAttributesChanged(): bool;

    /**
     * Optional Pinecone metadata filter so semantic search can scope to this model type.
     *
     * @return array<string, mixed>|null
     */
    public static function semanticSearchMetadataFilter(): ?array;
}
