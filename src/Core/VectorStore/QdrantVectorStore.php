<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Core\VectorStore;

use GuzzleHttp\Client;
use Vectora\Pinecone\Contracts\ProvidesVectorStoreCapabilities;
use Vectora\Pinecone\Contracts\VectorStoreContract;
use Vectora\Pinecone\Core\Exception\ApiException;
use Vectora\Pinecone\Core\Http\Json;
use Vectora\Pinecone\Core\VectorStore\Support\PineconeFilterForQdrant;
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
 * Qdrant REST driver (single unnamed vector per collection).
 *
 * Logical ids are unique per namespace; Qdrant ids are hashed (namespace + logical id).
 * Payload keys: {@see QdrantVectorStore::NS_PAYLOAD_KEY}, {@see QdrantVectorStore::PID_PAYLOAD_KEY}.
 */
final class QdrantVectorStore implements ProvidesVectorStoreCapabilities, VectorStoreContract
{
    private readonly string $baseUrl;

    public const NS_PAYLOAD_KEY = '_vectora_ns';

    public const PID_PAYLOAD_KEY = '_vectora_pid';

    public function __construct(
        private readonly Client $http,
        string $baseUrl,
        private readonly string $collection,
        private readonly ?string $apiKey = null,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function vectorStoreCapabilities(): VectorStoreCapabilities
    {
        return new VectorStoreCapabilities(
            backendName: 'qdrant',
            supportsNamespaces: true,
            supportsMetadataFilter: true,
            supportsDeleteByFilter: true,
            supportsDescribeIndexStats: true,
        );
    }

    public function upsert(UpsertVectorsRequest $request): UpsertResult
    {
        $ns = $this->normalizeNs($request->namespace);
        $points = [];
        foreach ($request->vectors as $v) {
            $payload = $v->metadata ?? [];
            $payload[self::NS_PAYLOAD_KEY] = $ns;
            $payload[self::PID_PAYLOAD_KEY] = $v->id;
            $points[] = [
                'id' => $this->internalPointId($ns, $v->id),
                'vector' => array_values(array_map('floatval', $v->values)),
                'payload' => $payload,
            ];
        }
        $this->postJson('/collections/'.$this->encCollection().'/points?wait=true', [
            'points' => $points,
        ]);

        return new UpsertResult(count($points));
    }

    public function query(QueryVectorsRequest $request): QueryVectorsResult
    {
        $ns = $this->normalizeNs($request->namespace);
        $vec = $request->vector;
        if ($request->queryByVectorId !== null) {
            $vec = $this->vectorForLogicalId($ns, $request->queryByVectorId) ?? $request->vector;
        }
        $filter = $this->mergeNsFilter(PineconeFilterForQdrant::convert($request->filter), $ns);
        $body = [
            'vector' => array_values(array_map('floatval', $vec)),
            'limit' => $request->topK,
            'with_payload' => true,
            'with_vector' => $request->includeValues,
            'filter' => $filter,
        ];
        $data = $this->postJson('/collections/'.$this->encCollection().'/points/search', $body);
        $matches = [];
        if (isset($data['result']) && is_array($data['result'])) {
            foreach ($data['result'] as $hit) {
                if (! is_array($hit)) {
                    continue;
                }
                $score = isset($hit['score']) && is_numeric($hit['score']) ? (float) $hit['score'] : 0.0;
                $payload = isset($hit['payload']) && is_array($hit['payload']) ? $hit['payload'] : [];
                $extId = isset($payload[self::PID_PAYLOAD_KEY]) && is_string($payload[self::PID_PAYLOAD_KEY])
                    ? $payload[self::PID_PAYLOAD_KEY]
                    : null;
                if ($extId === null || $extId === '') {
                    continue;
                }
                $meta = $payload;
                unset($meta[self::NS_PAYLOAD_KEY], $meta[self::PID_PAYLOAD_KEY]);
                $vecOut = null;
                if ($request->includeValues && isset($hit['vector'])) {
                    $vecOut = is_array($hit['vector']) ? array_map('floatval', $hit['vector']) : null;
                    if (is_array($vecOut) && isset($vecOut[0]) && is_array($vecOut[0])) {
                        $vecOut = null;
                    }
                }

                $matches[] = new QueryVectorMatch(
                    $extId,
                    $score,
                    $vecOut,
                    $request->includeMetadata ? $meta : null,
                );
            }
        }

        return new QueryVectorsResult($matches, $request->namespace, null);
    }

    public function delete(DeleteVectorsRequest $request): void
    {
        $ns = $this->normalizeNs($request->namespace);
        if ($request->deleteAll) {
            $filter = $this->mergeNsFilter(null, $ns);
            $this->postJson('/collections/'.$this->encCollection().'/points/delete?wait=true', [
                'filter' => $filter,
            ]);

            return;
        }
        if ($request->ids !== null) {
            $internal = [];
            foreach ($request->ids as $id) {
                $internal[] = $this->internalPointId($ns, $id);
            }
            $this->postJson('/collections/'.$this->encCollection().'/points/delete?wait=true', [
                'points' => $internal,
            ]);

            return;
        }
        if ($request->filter !== null) {
            $filter = $this->mergeNsFilter(PineconeFilterForQdrant::convert($request->filter), $ns);
            $this->postJson('/collections/'.$this->encCollection().'/points/delete?wait=true', [
                'filter' => $filter,
            ]);
        }
    }

    public function describeIndexStats(): DescribeIndexStatsResult
    {
        $response = $this->http->get($this->baseUrl.'/collections/'.$this->encCollection(), [
            'headers' => $this->headers(),
            'http_errors' => false,
        ]);
        $raw = (string) $response->getBody();
        if ($response->getStatusCode() >= 400) {
            throw new ApiException('Qdrant collection describe failed.', $response->getStatusCode(), $raw);
        }
        /** @var array<string, mixed> $data */
        $data = Json::decodeObject($raw);
        $result = isset($data['result']) && is_array($data['result']) ? $data['result'] : [];
        $total = isset($result['points_count']) && is_numeric($result['points_count']) ? (int) $result['points_count'] : 0;
        $dim = 0;
        $params = isset($result['config']) && is_array($result['config'])
            ? ($result['config']['params'] ?? [])
            : [];
        if (is_array($params)) {
            $vectors = $params['vectors'] ?? null;
            if (is_array($vectors)) {
                $size = $vectors['size'] ?? null;
                if (is_numeric($size)) {
                    $dim = (int) $size;
                } else {
                    foreach ($vectors as $v) {
                        if (is_array($v) && isset($v['size']) && is_numeric($v['size'])) {
                            $dim = max($dim, (int) $v['size']);
                        }
                    }
                }
            }
        }

        return new DescribeIndexStatsResult($dim, $total, $this->namespaceCountsViaScroll(), 'cosine');
    }

    /**
     * @return array<string, NamespaceSummary>
     */
    private function namespaceCountsViaScroll(): array
    {
        $counts = [];
        $offset = null;
        do {
            $body = [
                'limit' => 256,
                'with_payload' => true,
                'with_vector' => false,
            ];
            if ($offset !== null) {
                $body['offset'] = $offset;
            }
            $data = $this->postJson('/collections/'.$this->encCollection().'/points/scroll', $body);
            $pts = isset($data['result']['points']) && is_array($data['result']['points'])
                ? $data['result']['points']
                : [];
            foreach ($pts as $p) {
                if (! is_array($p)) {
                    continue;
                }
                $pl = isset($p['payload']) && is_array($p['payload']) ? $p['payload'] : [];
                $n = isset($pl[self::NS_PAYLOAD_KEY]) && is_string($pl[self::NS_PAYLOAD_KEY])
                    ? $pl[self::NS_PAYLOAD_KEY]
                    : '';
                $counts[$n] = ($counts[$n] ?? 0) + 1;
            }
            $offset = isset($data['result']['next_page_offset']) ? $data['result']['next_page_offset'] : null;
        } while ($offset !== null);

        $out = [];
        foreach ($counts as $name => $c) {
            $out[$name] = new NamespaceSummary($name, $c);
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function postJson(string $path, array $body): array
    {
        $response = $this->http->post($this->baseUrl.$path, [
            'headers' => $this->headers(),
            'json' => $body,
            'http_errors' => false,
        ]);
        $raw = (string) $response->getBody();
        if ($response->getStatusCode() >= 400) {
            throw new ApiException('Qdrant request failed: '.$path, $response->getStatusCode(), $raw);
        }

        return Json::decodeObject($raw);
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        $h = ['Content-Type' => 'application/json'];
        if ($this->apiKey !== null && $this->apiKey !== '') {
            $h['api-key'] = $this->apiKey;
        }

        return $h;
    }

    private function encCollection(): string
    {
        return rawurlencode($this->collection);
    }

    private function normalizeNs(?string $namespace): string
    {
        return $namespace ?? '';
    }

    private function internalPointId(string $ns, string $id): string
    {
        return bin2hex(hash('sha256', $ns."\0".$id, true));
    }

    /**
     * @param  array<string, mixed>|null  $pineconeFilter
     * @return array<string, mixed>
     */
    private function mergeNsFilter(?array $pineconeFilter, string $ns): array
    {
        $nsCond = [
            'key' => self::NS_PAYLOAD_KEY,
            'match' => ['value' => $ns],
        ];
        if ($pineconeFilter === null) {
            return ['must' => [$nsCond]];
        }
        if (isset($pineconeFilter['must']) && is_array($pineconeFilter['must'])) {
            return ['must' => array_merge([$nsCond], $pineconeFilter['must'])];
        }

        return ['must' => [$nsCond, $pineconeFilter]];
    }

    /**
     * @return list<float>|null
     */
    private function vectorForLogicalId(string $ns, string $id): ?array
    {
        $data = $this->postJson('/collections/'.$this->encCollection().'/points', [
            'ids' => [$this->internalPointId($ns, $id)],
            'with_vector' => true,
            'with_payload' => true,
        ]);
        $pts = isset($data['result']) && is_array($data['result']) ? $data['result'] : [];
        if ($pts === [] || ! is_array($pts[0])) {
            return null;
        }
        $p = $pts[0];
        $pl = isset($p['payload']) && is_array($p['payload']) ? $p['payload'] : [];
        $pns = $pl[self::NS_PAYLOAD_KEY] ?? null;
        if ($pns !== $ns) {
            return null;
        }
        $vec = isset($p['vector']) && is_array($p['vector']) ? $p['vector'] : null;
        if ($vec === null || (isset($vec[0]) && is_array($vec[0]))) {
            return null;
        }

        return array_map('floatval', $vec);
    }
}
