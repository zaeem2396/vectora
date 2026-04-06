<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Core\VectorStore;

use Vectora\Pinecone\Contracts\ProvidesVectorStoreCapabilities;
use Vectora\Pinecone\Contracts\VectorStoreContract;
use Vectora\Pinecone\Core\Http\Json;
use Vectora\Pinecone\Core\VectorStore\Support\MetadataFilterEvaluator;
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
 * Postgres + pgvector backend (cosine distance {@see PgVectorVectorStore::DISTANCE_OP}).
 *
 * Requires extension `vector` and a fixed column dimension matching {@see $dimensions}.
 */
final class PgVectorVectorStore implements ProvidesVectorStoreCapabilities, VectorStoreContract
{
    private const DISTANCE_OP = '<=>';

    private readonly string $quotedTable;

    public function __construct(
        private readonly \PDO $pdo,
        string $table,
        private readonly int $dimensions,
        private readonly bool $ensureSchema = false,
    ) {
        if ($this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) !== 'pgsql') {
            throw new \InvalidArgumentException('PgVectorVectorStore requires a pgsql PDO connection.');
        }
        $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $table) ?: 'vectora_vectors';
        $this->quotedTable = '"'.$safe.'"';
        if ($this->ensureSchema) {
            $this->pdo->exec('CREATE EXTENSION IF NOT EXISTS vector');
            $dim = max(1, $this->dimensions);
            $this->pdo->exec('CREATE TABLE IF NOT EXISTS '.$this->quotedTable.' (
                namespace TEXT NOT NULL DEFAULT \'\',
                id TEXT NOT NULL,
                embedding vector('.$dim.'),
                metadata JSONB,
                PRIMARY KEY(namespace, id)
            )');
        }
    }

    public function vectorStoreCapabilities(): VectorStoreCapabilities
    {
        return new VectorStoreCapabilities(
            backendName: 'pgvector',
            supportsNamespaces: true,
            supportsMetadataFilter: true,
            supportsDeleteByFilter: true,
            supportsDescribeIndexStats: true,
        );
    }

    public function upsert(UpsertVectorsRequest $request): UpsertResult
    {
        $ns = $this->normalizeNs($request->namespace);
        $sql = 'INSERT INTO '.$this->quotedTable.' (namespace, id, embedding, metadata) VALUES (?, ?, ?::vector, ?::jsonb)
            ON CONFLICT (namespace, id) DO UPDATE SET embedding = EXCLUDED.embedding, metadata = EXCLUDED.metadata';
        $stmt = $this->pdo->prepare($sql);
        foreach ($request->vectors as $v) {
            $vec = array_values(array_map('floatval', $v->values));
            $metaJson = $v->metadata !== null && $v->metadata !== [] ? Json::encode($v->metadata) : null;
            $stmt->execute([
                $ns,
                $v->id,
                $this->vectorLiteral($vec),
                $metaJson,
            ]);
        }

        return new UpsertResult(count($request->vectors));
    }

    public function query(QueryVectorsRequest $request): QueryVectorsResult
    {
        $ns = $this->normalizeNs($request->namespace);
        $qvec = $request->vector;
        if ($request->queryByVectorId !== null) {
            $stmt = $this->pdo->prepare('SELECT embedding::text FROM '.$this->quotedTable.' WHERE namespace = ? AND id = ?');
            $stmt->execute([$ns, $request->queryByVectorId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (is_array($row) && isset($row['embedding']) && is_string($row['embedding'])) {
                $parsed = $this->parseVectorText($row['embedding']);
                if ($parsed !== null) {
                    $qvec = $parsed;
                }
            }
        }
        $prefetch = max($request->topK * 5, 32);
        $sql = 'SELECT id, metadata, embedding::text AS emb_text,
            1 - (embedding '.self::DISTANCE_OP.' ?::vector) AS score
            FROM '.$this->quotedTable.'
            WHERE namespace = ?
            ORDER BY embedding '.self::DISTANCE_OP.' ?::vector
            LIMIT '.$prefetch;
        $stmt = $this->pdo->prepare($sql);
        $lit = $this->vectorLiteral(array_values(array_map('floatval', $qvec)));
        $stmt->execute([$lit, $ns, $lit]);
        $matches = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if (! is_array($row)) {
                continue;
            }
            $meta = null;
            if (isset($row['metadata']) && is_string($row['metadata']) && $row['metadata'] !== '') {
                $meta = Json::decodeObject($row['metadata']);
            }
            if (! MetadataFilterEvaluator::matches($meta, $request->filter)) {
                continue;
            }
            $id = (string) ($row['id'] ?? '');
            $score = isset($row['score']) && is_numeric($row['score']) ? (float) $row['score'] : 0.0;
            $vecOut = null;
            if ($request->includeValues && isset($row['emb_text']) && is_string($row['emb_text'])) {
                $vecOut = $this->parseVectorText($row['emb_text']);
            }
            $matches[] = new QueryVectorMatch(
                $id,
                $score,
                $vecOut,
                $request->includeMetadata ? $meta : null,
            );
            if (count($matches) >= $request->topK) {
                break;
            }
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
            $stmt = $this->pdo->prepare('SELECT id, metadata FROM '.$this->quotedTable.' WHERE namespace = ?');
            $stmt->execute([$ns]);
            $del = $this->pdo->prepare('DELETE FROM '.$this->quotedTable.' WHERE namespace = ? AND id = ?');
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $meta = null;
                if (isset($row['metadata']) && is_string($row['metadata']) && $row['metadata'] !== '') {
                    $meta = Json::decodeObject($row['metadata']);
                }
                if (MetadataFilterEvaluator::matches($meta, $request->filter)) {
                    $del->execute([$ns, (string) $row['id']]);
                }
            }
        }
    }

    public function describeIndexStats(): DescribeIndexStatsResult
    {
        $stmt = $this->pdo->query('SELECT namespace, COUNT(*) AS c FROM '.$this->quotedTable.' GROUP BY namespace');
        $namespaces = [];
        $total = 0;
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            /** @var array<string, mixed> $row */
            $n = (string) $row['namespace'];
            $c = (int) $row['c'];
            $total += $c;
            $namespaces[$n] = new NamespaceSummary($n, $c);
        }

        return new DescribeIndexStatsResult($this->dimensions, $total, $namespaces, 'cosine');
    }

    /**
     * @param  list<float>  $values
     */
    private function vectorLiteral(array $values): string
    {
        $parts = array_map(static fn (float $x): string => sprintf('%0.8e', $x), $values);

        return '['.implode(',', $parts).']';
    }

    /**
     * @return list<float>|null
     */
    private function parseVectorText(string $text): ?array
    {
        $t = trim($text);
        if ($t === '') {
            return null;
        }
        if ($t[0] === '[') {
            $t = trim($t, '[]');
        }
        $parts = preg_split('/[\s,]+/', $t, -1, PREG_SPLIT_NO_EMPTY);
        if ($parts === false || $parts === []) {
            return null;
        }

        return array_map('floatval', $parts);
    }

    private function normalizeNs(?string $namespace): string
    {
        return $namespace ?? '';
    }
}
