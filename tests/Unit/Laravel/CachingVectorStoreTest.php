<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Laravel;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Contracts\VectorStoreContract;
use Vectora\Pinecone\DTO\DeleteVectorsRequest;
use Vectora\Pinecone\DTO\DescribeIndexStatsResult;
use Vectora\Pinecone\DTO\QueryVectorMatch;
use Vectora\Pinecone\DTO\QueryVectorsRequest;
use Vectora\Pinecone\DTO\QueryVectorsResult;
use Vectora\Pinecone\DTO\UpsertResult;
use Vectora\Pinecone\DTO\UpsertVectorsRequest;
use Vectora\Pinecone\Laravel\CachingVectorStore;

final class CachingVectorStoreTest extends TestCase
{
    public function test_query_is_cached_so_inner_is_called_once(): void
    {
        $inner = new class implements VectorStoreContract
        {
            public int $queryCalls = 0;

            public function upsert(UpsertVectorsRequest $request): UpsertResult
            {
                throw new \RuntimeException('not used');
            }

            public function query(QueryVectorsRequest $request): QueryVectorsResult
            {
                $this->queryCalls++;

                return new QueryVectorsResult([
                    new QueryVectorMatch('id-1', 0.9, null, null),
                ], $request->namespace, null);
            }

            public function delete(DeleteVectorsRequest $request): void {}

            public function describeIndexStats(): DescribeIndexStatsResult
            {
                throw new \RuntimeException('not used');
            }
        };

        $cache = new Repository(new ArrayStore);
        $store = new CachingVectorStore($inner, $cache, 'pfx', 3600, 'idx|host|ns');
        $req = new QueryVectorsRequest([0.1, 0.2], 3);

        $r1 = $store->query($req);
        $r2 = $store->query($req);

        $this->assertSame(1, $inner->queryCalls);
        $this->assertSame($r1->matches[0]->id, $r2->matches[0]->id);
    }
