<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Core\VectorStore;

use Vectora\Pinecone\Contracts\ProvidesVectorStoreCapabilities;
use Vectora\Pinecone\Contracts\VectorStoreContract;
use Vectora\Pinecone\Core\Http\Json;
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
 * File-backed SQLite store for integration tests and local tooling.
 *
 * Vectors are stored as JSON arrays; similarity is computed in PHP per query.
 */
final class SqliteVectorStore implements ProvidesVectorStoreCapabilities, VectorStoreContract
{
    private readonly string $quotedTable;

    public function __construct(
        private readonly \PDO $pdo,
        string $table = 'vectora_vectors',
    ) {
        $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $table) ?: 'vectora_vectors';
        $this->quotedTable = '"'.$safe.'"';
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS '.$this->quotedTable.' (
            namespace TEXT NOT NULL DEFAULT \'\',
            id TEXT NOT NULL,
            dim INTEGER NOT NULL,
            values_json TEXT NOT NULL,
            metadata_json TEXT,
            PRIMARY KEY(namespace, id)
        )');
    }

    public static function open(string $path, string $table = 'vectora_vectors'): self
    {
        $pdo = new \PDO('sqlite:'.$path, null, null, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ]);

        return new self($pdo, $table);
    }

    public function vectorStoreCapabilities(): VectorStoreCapabilities
    {
        return new VectorStoreCapabilities(
            backendName: 'sqlite',
            supportsNamespaces: true,
            supportsMetadataFilter: true,
            supportsDeleteByFilter: true,
            supportsDescribeIndexStats: true,
        );
    }

    public function upsert(UpsertVectorsRequest $request): UpsertResult
    {
        $ns = $this->normalizeNs($request->namespace);
        $stmt = $this->pdo->prepare(
            'INSERT OR REPLACE INTO '.$this->quotedTable.' (namespace, id, dim, values_json, metadata_json) VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($request->vectors as $v) {
            $values = array_values(array_map('floatval', $v->values));
            $metaJson = $v->metadata !== null && $v->metadata !== [] ? Json::encode($v->metadata) : null;
            $stmt->execute([
                $ns,
                $v->id,
                count($values),
                Json::encode(['v' => $values]),
                $metaJson,
            ]);
        }

        return new UpsertResult(count($request->vectors));
    }

    public function query(QueryVectorsRequest $request): QueryVectorsResult
    {
        $ns = $this->normalizeNs($request->namespace);
        $stmt = $this->pdo->prepare('SELECT id, values_json, metadata_json FROM '.$this->quotedTable.' WHERE namespace = ?');
        $stmt->execute([$ns]);
        $rows = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            /** @var array<string, mixed> $row */
            $decoded = Json::decodeObject((string) $row['values_json']);
            /** @var list<float> $vec */
            $vec = array_map('floatval', $decoded['v'] ?? []);
            $meta = null;
            if (isset($row['metadata_json']) && is_string($row['metadata_json']) && $row['metadata_json'] !== '') {
                $meta = Json::decodeObject($row['metadata_json']);
            }
            $rows[(string) $row['id']] = ['values' => $vec, 'metadata' => $meta];
        }

        $qvec = $request->vector;
        if ($request->queryByVectorId !== null) {
            $qvec = $rows[$request->queryByVectorId]['values'] ?? $request->vector;
        }

        $scored = [];
        foreach ($rows as $id => $r) {
            if (! MetadataFilterEvaluator::matches($r['metadata'], $request->filter)) {
                continue;
            }
            $score = VectorMath::cosineSimilarity($qvec, $r['values']);
            $scored[] = [$id, $score, $r];
        }
        usort($scored, static fn (array $x, array $y): int => $y[1] <=> $x[1]);
        $scored = array_slice($scored, 0, $request->topK);

        $matches = [];
        foreach ($scored as [$id, $score, $r]) {
            $matches[] = new QueryVectorMatch(
                $id,
                $score,
                $request->includeValues ? $r['values'] : null,
                $request->includeMetadata ? $r['metadata'] : null,
            );
        }

        return new QueryVectorsResult($matches, $request->namespace, null);
    }

    public function delete(DeleteVectorsRequest $request): void
    {
        $ns = $this->normalizeNs($request->namespace);
        if ($request->deleteAll) {
            $s = $this->pdo->prepare('DELETE FROM '.$this->quotedTable.' WHERE namespace = ?');
            $s->execute([$ns]);

            return;
        }
        if ($request->ids !== null) {
            $s = $this->pdo->prepare('DELETE FROM '.$this->quotedTable.' WHERE namespace = ? AND id = ?');
            foreach ($request->ids as $id) {
                $s->execute([$ns, $id]);
            }

            return;
        }
        if ($request->filter !== null) {
            $stmt = $this->pdo->prepare('SELECT id, metadata_json FROM '.$this->quotedTable.' WHERE namespace = ?');
            $stmt->execute([$ns]);
            $del = $this->pdo->prepare('DELETE FROM '.$this->quotedTable.' WHERE namespace = ? AND id = ?');
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $meta = null;
                if (isset($row['metadata_json']) && is_string($row['metadata_json']) && $row['metadata_json'] !== '') {
                    $meta = Json::decodeObject($row['metadata_json']);
                }
                if (MetadataFilterEvaluator::matches($meta, $request->filter)) {
                    $del->execute([$ns, (string) $row['id']]);
                }
            }
        }
    }

    public function describeIndexStats(): DescribeIndexStatsResult
    {
        $stmt = $this->pdo->query('SELECT namespace, COUNT(*) as c, MAX(dim) as d FROM '.$this->quotedTable.' GROUP BY namespace');
        $namespaces = [];
        $total = 0;
        $dim = 0;
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            /** @var array<string, mixed> $row */
            $n = (string) $row['namespace'];
            $c = (int) $row['c'];
            $total += $c;
            $dim = max($dim, (int) $row['d']);
            $namespaces[$n] = new NamespaceSummary($n, $c);
        }

        return new DescribeIndexStatsResult($dim, $total, $namespaces, 'cosine');
    }

    private function normalizeNs(?string $namespace): string
    {
        return $namespace ?? '';
    }
}
