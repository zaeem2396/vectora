<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Support;

use Vectora\Pinecone\Contracts\VectorStoreContract;
use Vectora\Pinecone\DTO\DeleteVectorsRequest;
use Vectora\Pinecone\DTO\DescribeIndexStatsResult;
use Vectora\Pinecone\DTO\QueryVectorMatch;
use Vectora\Pinecone\DTO\QueryVectorsRequest;
use Vectora\Pinecone\DTO\QueryVectorsResult;
use Vectora\Pinecone\DTO\UpsertResult;
use Vectora\Pinecone\DTO\UpsertVectorsRequest;

final class RecordingVectorStore implements VectorStoreContract
{
    /** @var list<UpsertVectorsRequest> */
    public array $upsertRequests = [];

    /** @var list<DeleteVectorsRequest> */
    public array $deleteRequests = [];

    public ?QueryVectorsRequest $lastQueryRequest = null;

    /** @var list<QueryVectorMatch> */
    public array $queryMatches = [];

    public int $queryCallCount = 0;

    public function upsert(UpsertVectorsRequest $request): UpsertResult
    {
        $this->upsertRequests[] = $request;

        return new UpsertResult(count($request->vectors));
    }

    public function query(QueryVectorsRequest $request): QueryVectorsResult
    {
        $this->lastQueryRequest = $request;
        $this->queryCallCount++;

        return new QueryVectorsResult($this->queryMatches, $request->namespace, null);
    }

    public function delete(DeleteVectorsRequest $request): void
    {
        $this->deleteRequests[] = $request;
    }

    public function describeIndexStats(): DescribeIndexStatsResult
    {
        return new DescribeIndexStatsResult(8, 0, []);
    }
}
