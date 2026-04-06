<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Core\VectorStore;

use GuzzleHttp\Client;
use Vectora\Pinecone\Contracts\ProvidesVectorStoreCapabilities;
use Vectora\Pinecone\Contracts\VectorStoreContract;
use Vectora\Pinecone\Core\Exception\ApiException;
use Vectora\Pinecone\Core\Http\Json;
use Vectora\Pinecone\Core\VectorStore\Support\MetadataFilterEvaluator;
use Vectora\Pinecone\Core\VectorStore\Support\WeaviateUuid;
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
 * Weaviate driver (class must use vectorizer: none; vectors set at upsert).
 *
 * Properties: {@see WeaviateVectorStore::PROP_NS}, {@see WeaviateVectorStore::PROP_META} (JSON blob of Pinecone-style metadata).
 */
final class WeaviateVectorStore implements ProvidesVectorStoreCapabilities, VectorStoreContract
{
    private readonly string $baseUrl;

    public const PROP_NS = 'vectoraNs';

    public const PROP_META = 'vectoraMeta';

    public function __construct(
        private readonly Client $http,
        string $baseUrl,
        private readonly string $className,
        private readonly ?string $apiKey = null,
        private readonly int $queryPrefetchMultiplier = 4,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function vectorStoreCapabilities(): VectorStoreCapabilities
    {
        return new VectorStoreCapabilities(
            backendName: 'weaviate',
            supportsNamespaces: true,
            supportsMetadataFilter: true,
            supportsDeleteByFilter: true,
            supportsDescribeIndexStats: true,
        );
    }

    public function upsert(UpsertVectorsRequest $request): UpsertResult
    {
        $ns = $this->normalizeNs($request->namespace);
        foreach ($request->vectors as $v) {
            $uuid = WeaviateUuid::fromNamespaceAndId($ns, $v->id);
            $meta = $v->metadata ?? [];
            $url = $this->baseUrl.'/v1/objects/'.$this->enc($this->className).'/'.$uuid;
            $response = $this->http->put($url, [
                'headers' => $this->headers(),
                'json' => [
                    'class' => $this->className,
                    'properties' => [
                        self::PROP_NS => $ns,
                        self::PROP_META => Json::encode($meta),
                    ],
                    'vector' => array_values(array_map('floatval', $v->values)),
                ],
                'http_errors' => false,
            ]);
            $this->assertOk($response->getStatusCode(), (string) $response->getBody(), 'weaviate upsert');
        }

        return new UpsertResult(count($request->vectors));
    }

    public function query(QueryVectorsRequest $request): QueryVectorsResult
    {
        $ns = $this->normalizeNs($request->namespace);
        $vec = $request->vector;
        if ($request->queryByVectorId !== null) {
            $lv = $this->loadVector($ns, $request->queryByVectorId);
            if ($lv !== null) {
                $vec = $lv;
            }
        }
        $limit = max($request->topK * $this->queryPrefetchMultiplier, $request->topK + 8, 16);
        $class = $this->graphqlClass();
        $query = <<<GQL
            query Near(\$vector: [Float!]!, \$ns: String!, \$lim: Int!) {
              Get {
                {$class}(
                  nearVector: { vector: \$vector }
                  limit: \$lim
                  where: {
                    path: ["{$this->graphqlProp(self::PROP_NS)}"]
                    operator: Equal
                    valueText: \$ns
                  }
                ) {
                  {$this->graphqlProp(self::PROP_META)}
                  _additional { id distance }
                }
              }
            }
            GQL;
        $raw = $this->graphql($query, [
            'vector' => array_values(array_map('floatval', $vec)),
            'ns' => $ns,
            'lim' => $limit,
        ]);
        /** @var array<string, mixed> $decoded */
        $decoded = Json::decodeObject($raw);
        $items = $decoded['data']['Get'][$class] ?? [];
        if (! is_array($items)) {
            $items = [];
        }
        $scored = [];
        foreach ($items as $row) {
            if (! is_array($row)) {
                continue;
            }
            $metaJson = $row[self::PROP_META] ?? null;
            $meta = null;
            if (is_string($metaJson) && $metaJson !== '') {
                try {
                    $meta = Json::decodeObject($metaJson);
                } catch (\InvalidArgumentException) {
                    $meta = [];
                }
            }
            if (! MetadataFilterEvaluator::matches($meta, $request->filter)) {
                continue;
            }
            $add = isset($row['_additional']) && is_array($row['_additional']) ? $row['_additional'] : [];
            $dist = isset($add['distance']) && is_numeric($add['distance']) ? (float) $add['distance'] : null;
            $score = $dist !== null ? max(0.0, 1.0 - $dist) : 0.0;
            $wid = isset($add['id']) && is_string($add['id']) ? $add['id'] : '';
            $logicalId = $this->logicalIdFromMeta($meta, $wid);
            $scored[] = [$logicalId, $score, $meta];
        }
        usort($scored, static fn (array $x, array $y): int => $y[1] <=> $x[1]);
        $scored = array_slice($scored, 0, $request->topK);
        $matches = [];
        foreach ($scored as [$lid, $score, $meta]) {
            $matches[] = new QueryVectorMatch(
                $lid,
                $score,
                null,
                $request->includeMetadata ? $meta : null,
            );
        }

        return new QueryVectorsResult($matches, $request->namespace, null);
    }

    public function delete(DeleteVectorsRequest $request): void
    {
        $ns = $this->normalizeNs($request->namespace);
        if ($request->deleteAll) {
            $this->deleteAllInNamespace($ns);

            return;
        }
        if ($request->ids !== null) {
            foreach ($request->ids as $id) {
                $uuid = WeaviateUuid::fromNamespaceAndId($ns, $id);
                $url = $this->baseUrl.'/v1/objects/'.$this->enc($this->className).'/'.$uuid;
                $response = $this->http->delete($url, [
                    'headers' => $this->headers(),
                    'http_errors' => false,
                ]);
                if ($response->getStatusCode() === 404) {
                    continue;
                }
                $this->assertOk($response->getStatusCode(), (string) $response->getBody(), 'weaviate delete');
            }

            return;
        }
        if ($request->filter !== null) {
            $this->deleteByFilterScan($ns, $request->filter);
        }
    }

    public function describeIndexStats(): DescribeIndexStatsResult
    {
        $class = $this->graphqlClass();
        $gq = <<<GQL
            {
              Aggregate {
                {$class} {
                  meta { count }
                }
              }
            }
            GQL;
        $raw = $this->graphql($gq, null);
        /** @var array<string, mixed> $decoded */
        $decoded = Json::decodeObject($raw);
        $agg = $decoded['data']['Aggregate'][$class] ?? [];
        $total = 0;
        if (is_array($agg) && isset($agg[0]) && is_array($agg[0])) {
            $m = $agg[0]['meta'] ?? [];
            if (is_array($m) && isset($m['count']) && is_numeric($m['count'])) {
                $total = (int) $m['count'];
            }
        }
        $namespaces = $this->namespaceCountsAggregate();

        return new DescribeIndexStatsResult(0, $total, $namespaces, 'cosine');
    }

    /**
     * @return array<string, NamespaceSummary>
     */
    private function namespaceCountsAggregate(): array
    {
        $class = $this->graphqlClass();
        $prop = $this->graphqlProp(self::PROP_NS);
        $gq = <<<GQL
            {
              Aggregate {
                {$class}(groupBy: ["{$prop}"]) {
                  groupedBy { path value }
                  meta { count }
                }
              }
            }
            GQL;
        try {
            $raw = $this->graphql($gq, null);
        } catch (\Throwable) {
            return [];
        }
        /** @var array<string, mixed> $decoded */
        $decoded = Json::decodeObject($raw);
        $groups = $decoded['data']['Aggregate'][$class] ?? [];
        $out = [];
        if (! is_array($groups)) {
            return [];
        }
        foreach ($groups as $g) {
            if (! is_array($g)) {
                continue;
            }
            $gb = $g['groupedBy'] ?? [];
            $val = is_array($gb) && isset($gb['value']) && is_string($gb['value']) ? $gb['value'] : '';
            $c = 0;
            $meta = $g['meta'] ?? [];
            if (is_array($meta) && isset($meta['count']) && is_numeric($meta['count'])) {
                $c = (int) $meta['count'];
            }
            $out[$val] = new NamespaceSummary($val, $c);
        }

        return $out;
    }

    private function deleteAllInNamespace(string $ns): void
    {
        while (true) {
            $ids = $this->listObjectIdsInNamespace($ns, 64);
            if ($ids === []) {
                break;
            }
            foreach ($ids as $wid) {
                $url = $this->baseUrl.'/v1/objects/'.$this->enc($this->className).'/'.$wid;
                $response = $this->http->delete($url, [
                    'headers' => $this->headers(),
                    'http_errors' => false,
                ]);
                if ($response->getStatusCode() >= 400 && $response->getStatusCode() !== 404) {
                    $this->assertOk($response->getStatusCode(), (string) $response->getBody(), 'weaviate delete all');
                }
            }
        }
    }

    /**
     * @return list<string>
     */
    private function listObjectIdsInNamespace(string $ns, int $limit): array
    {
        $class = $this->graphqlClass();
        $query = <<<GQL
            query List(\$ns: String!, \$lim: Int!) {
              Get {
                {$class}(
                  limit: \$lim
                  where: {
                    path: ["{$this->graphqlProp(self::PROP_NS)}"]
                    operator: Equal
                    valueText: \$ns
                  }
                ) {
                  _additional { id }
                }
              }
            }
            GQL;
        $raw = $this->graphql($query, ['ns' => $ns, 'lim' => $limit]);
        /** @var array<string, mixed> $decoded */
        $decoded = Json::decodeObject($raw);
        $items = $decoded['data']['Get'][$class] ?? [];
        if (! is_array($items)) {
            return [];
        }
        $ids = [];
        foreach ($items as $row) {
            if (! is_array($row)) {
                continue;
            }
            $add = $row['_additional'] ?? [];
            if (is_array($add) && isset($add['id']) && is_string($add['id'])) {
                $ids[] = $add['id'];
            }
        }

        return $ids;
    }

    /**
     * @param  array<string, mixed>  $filter
     */
    private function deleteByFilterScan(string $ns, array $filter): void
    {
        $class = $this->graphqlClass();
        $limit = 500;
        $offset = 0;
        $query = <<<GQL
            query Scan(\$ns: String!, \$lim: Int!, \$off: Int!) {
              Get {
                {$class}(
                  limit: \$lim
                  offset: \$off
                  where: {
                    path: ["{$this->graphqlProp(self::PROP_NS)}"]
                    operator: Equal
                    valueText: \$ns
                  }
                ) {
                  {$this->graphqlProp(self::PROP_META)}
                  _additional { id }
                }
              }
            }
            GQL;

        while (true) {
            $raw = $this->graphql($query, ['ns' => $ns, 'lim' => $limit, 'off' => $offset]);
            /** @var array<string, mixed> $decoded */
            $decoded = Json::decodeObject($raw);
            $items = $decoded['data']['Get'][$class] ?? [];
            if (! is_array($items) || $items === []) {
                break;
            }

            $deletedAny = false;
            foreach ($items as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $metaJson = $row[self::PROP_META] ?? null;
                $meta = null;
                if (is_string($metaJson) && $metaJson !== '') {
                    try {
                        $meta = Json::decodeObject($metaJson);
                    } catch (\InvalidArgumentException) {
                        $meta = [];
                    }
                }
                if (! MetadataFilterEvaluator::matches($meta, $filter)) {
                    continue;
                }
                $add = isset($row['_additional']) && is_array($row['_additional']) ? $row['_additional'] : [];
                $wid = isset($add['id']) && is_string($add['id']) ? $add['id'] : '';
                if ($wid === '') {
                    continue;
                }
                $url = $this->baseUrl.'/v1/objects/'.$this->enc($this->className).'/'.$wid;
                $response = $this->http->delete($url, [
                    'headers' => $this->headers(),
                    'http_errors' => false,
                ]);
                if ($response->getStatusCode() >= 400 && $response->getStatusCode() !== 404) {
                    $this->assertOk($response->getStatusCode(), (string) $response->getBody(), 'weaviate delete filter');
                }
                $deletedAny = true;
            }

            $batchCount = count($items);
            if ($batchCount < $limit) {
                break;
            }
            if ($deletedAny) {
                $offset = 0;
            } else {
                $offset += $limit;
            }
        }
    }

    /**
     * @return list<float>|null
     */
    private function loadVector(string $ns, string $id): ?array
    {
        $uuid = WeaviateUuid::fromNamespaceAndId($ns, $id);
        $url = $this->baseUrl.'/v1/objects/'.$this->enc($this->className).'/'.$uuid.'?include=vector';
        $response = $this->http->get($url, [
            'headers' => $this->headers(),
            'http_errors' => false,
        ]);
        if ($response->getStatusCode() !== 200) {
            return null;
        }
        /** @var array<string, mixed> $data */
        $data = Json::decodeObject((string) $response->getBody());
        $vec = $data['vector'] ?? null;
        if (! is_array($vec)) {
            return null;
        }
        $props = $data['properties'] ?? [];
        $pns = is_array($props) && isset($props[self::PROP_NS]) && is_string($props[self::PROP_NS])
            ? $props[self::PROP_NS]
            : null;
        if ($pns !== $ns) {
            return null;
        }

        return array_map('floatval', $vec);
    }

    /**
     * @param  array<string, mixed>|null  $meta
     */
    private function logicalIdFromMeta(?array $meta, string $fallback): string
    {
        if ($meta !== null) {
            $k = $meta['vectora_key'] ?? null;
            if (is_string($k) || is_int($k)) {
                return (string) $k;
            }
        }

        return $fallback;
    }

    /**
     * @param  array<string, mixed>|null  $variables
     */
    private function graphql(string $query, ?array $variables): string
    {
        $payload = ['query' => $query];
        if ($variables !== null) {
            $payload['variables'] = $variables;
        }
        $response = $this->http->post($this->baseUrl.'/v1/graphql', [
            'headers' => $this->headers(),
            'json' => $payload,
            'http_errors' => false,
        ]);
        $raw = (string) $response->getBody();
        if ($response->getStatusCode() >= 400) {
            throw new ApiException('Weaviate GraphQL failed.', $response->getStatusCode(), $raw);
        }
        /** @var array<string, mixed> $decoded */
        $decoded = Json::decodeObject($raw);
        if (isset($decoded['errors']) && is_array($decoded['errors']) && $decoded['errors'] !== []) {
            $first = $decoded['errors'][0];
            $msg = is_array($first) && isset($first['message']) && is_string($first['message'])
                ? $first['message']
                : 'GraphQL error';

            throw new ApiException('Weaviate GraphQL: '.$msg, 400, $raw);
        }

        return $raw;
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        $h = ['Content-Type' => 'application/json'];
        if ($this->apiKey !== null && $this->apiKey !== '') {
            $h['Authorization'] = 'Bearer '.$this->apiKey;
        }

        return $h;
    }

    private function enc(string $s): string
    {
        return rawurlencode($s);
    }

    private function normalizeNs(?string $namespace): string
    {
        return $namespace ?? '';
    }

    private function graphqlClass(): string
    {
        return $this->className;
    }

    private function graphqlProp(string $p): string
    {
        return $p;
    }

    private function assertOk(int $status, string $body, string $ctx): void
    {
        if ($status >= 400) {
            throw new ApiException($ctx.' failed.', $status, $body);
        }
    }
}
