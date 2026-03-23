<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Contracts;

use Vectora\Pinecone\DTO\DeleteVectorsRequest;
use Vectora\Pinecone\DTO\DescribeIndexStatsResult;
use Vectora\Pinecone\DTO\QueryVectorsRequest;
use Vectora\Pinecone\DTO\QueryVectorsResult;
use Vectora\Pinecone\DTO\UpsertResult;
use Vectora\Pinecone\DTO\UpsertVectorsRequest;

/** Framework-agnostic vector index operations (implementations: Pinecone, future backends). */
interface VectorStoreContract
{
    public function upsert(UpsertVectorsRequest $request): UpsertResult;

    public function query(QueryVectorsRequest $request): QueryVectorsResult;

    public function delete(DeleteVectorsRequest $request): void;

    /** Unfiltered index-wide stats only (Pinecone serverless does not support filtered describe_index_stats). */
    public function describeIndexStats(): DescribeIndexStatsResult;
}
