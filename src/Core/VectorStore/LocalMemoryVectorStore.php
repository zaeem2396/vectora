<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Core\VectorStore;

use Vectora\Pinecone\Contracts\ProvidesVectorStoreCapabilities;
use Vectora\Pinecone\Contracts\VectorStoreContract;
use Vectora\Pinecone\Core\VectorStore\Support\MetadataFilterEvaluator;
use Vectora\Pinecone\Core\VectorStore\Support\VectorMath;
use Vectora\Pinecone\DTO\DeleteVectorsRequest;
use Vectora\Pinecone\DTO\DescribeIndexStatsResult;
use Vectora\Pinecone\DTO\NamespaceSummary;
use Vectora\Pinecone\DTO\QueryVectorMatch;
use Vectora\Pinecone\DTO\QueryVectorsRequest;
use Vectora\Pinecone\DTO\QueryVectorsResult;
use Vectora\Pinecone\DTO\UpsertResult;
use Vectora\Pinecone\DTO\UpsertVectorsRequest;
use Vectora\Pinecone\DTO\VectorStoreCapabilities;

/**
 * In-process vector index for tests and local development (no HTTP).
 *
 * @phpstan-type Row array{values: list<float>, metadata: ?array<string, mixed>}
 */
final class LocalMemoryVectorStore implements ProvidesVectorStoreCapabilities, VectorStoreContract
{
    /** @var array<string, array<string, Row>> namespace => id => row */
    private array $data = [];

    public function vectorStoreCapabilities(): VectorStoreCapabilities
    {
        return new VectorStoreCapabilities(
            backendName: 'memory',
            supportsNamespaces: true,
            supportsMetadataFilter: true,
            supportsDeleteByFilter: true,
            supportsDescribeIndexStats: true,
        );
    }

    public function upsert(UpsertVectorsRequest $request): UpsertResult
    {
        $ns = $this->normalizeNamespace($request->namespace);
        foreach ($request->vectors as $v) {
            $this->data[$ns][$v->id] = [
                'values' => array_values(array_map('floatval', $v->values)),
                'metadata' => $v->metadata,
            ];
        }

        return new UpsertResult(count($request->vectors));
    }

    public function query(QueryVectorsRequest $request): QueryVectorsResult
    {
        $ns = $this->normalizeNamespace($request->namespace);
        $rows = $this->data[$ns] ?? [];
        $qvec = $request->queryByVectorId !== null
            ? ($rows[$request->queryByVectorId]['values'] ?? $request->vector)
            : $request->vector;
        $scored = [];
        foreach ($rows as $id => $row) {
            if (! MetadataFilterEvaluator::matches($row['metadata'], $request->filter)) {
                continue;
            }
            $score = VectorMath::cosineSimilarity($qvec, $row['values']);
            $scored[] = [$id, $score, $row];
        }
        usort($scored, static fn (array $x, array $y): int => $y[1] <=> $x[1]);
        $scored = array_slice($scored, 0, $request->topK);

        $matches = [];
        foreach ($scored as [$id, $score, $row]) {
            $matches[] = new QueryVectorMatch(
                (string) $id,
                $score,
                $request->includeValues ? $row['values'] : null,
                $request->includeMetadata ? $row['metadata'] : null,
            );
        }

        return new QueryVectorsResult($matches, $request->namespace, null);
    }

    public function delete(DeleteVectorsRequest $request): void
    {
        $ns = $this->normalizeNamespace($request->namespace);
        if (! isset($this->data[$ns])) {
            return;
        }
        if ($request->deleteAll) {
            unset($this->data[$ns]);

            return;
        }
        if ($request->ids !== null) {
            foreach ($request->ids as $id) {
                unset($this->data[$ns][$id]);
            }

            return;
        }
        if ($request->filter !== null) {
            foreach ($this->data[$ns] as $id => $row) {
                if (MetadataFilterEvaluator::matches($row['metadata'], $request->filter)) {
                    unset($this->data[$ns][$id]);
                }
            }
        }
    }

    public function describeIndexStats(): DescribeIndexStatsResult
    {
        $dim = 0;
        $total = 0;
        $namespaces = [];
        foreach ($this->data as $ns => $rows) {
            $c = count($rows);
            $total += $c;
            $namespaces[$ns] = new NamespaceSummary($ns, $c);
            foreach ($rows as $row) {
                $dim = max($dim, count($row['values']));
            }
        }

        return new DescribeIndexStatsResult($dim, $total, $namespaces, 'cosine');
    }

    /** @internal Testing */
    public function clear(): void
    {
        $this->data = [];
    }

    private function normalizeNamespace(?string $namespace): string
    {
        return $namespace ?? '';
    }
}
