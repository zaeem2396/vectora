<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Core\Pinecone;

use Psr\Http\Message\ResponseInterface;
use Vectora\Pinecone\Contracts\VectorStoreContract;
use Vectora\Pinecone\Core\Http\Json;
use Vectora\Pinecone\Core\Http\PineconeHttpTransport;
use Vectora\Pinecone\DTO\DeleteVectorsRequest;
use Vectora\Pinecone\DTO\DescribeIndexStatsResult;
use Vectora\Pinecone\DTO\NamespaceSummary;
use Vectora\Pinecone\DTO\QueryVectorMatch;
use Vectora\Pinecone\DTO\QueryVectorsRequest;
use Vectora\Pinecone\DTO\QueryVectorsResult;
use Vectora\Pinecone\DTO\UpsertResult;
use Vectora\Pinecone\DTO\UpsertVectorsRequest;

final class PineconeVectorStore implements VectorStoreContract
{
    private readonly string $dataBaseUrl;

    private readonly ?string $defaultNamespace;

    public function __construct(
        private readonly PineconeHttpTransport $transport,
        string $indexHost,
        ?string $defaultNamespace = null,
    ) {
        $this->dataBaseUrl = PineconeHttpTransport::normalizeBaseUrl($indexHost);
        $this->defaultNamespace = $defaultNamespace !== null && $defaultNamespace !== ''
            ? $defaultNamespace
            : null;
    }

    public function upsert(UpsertVectorsRequest $request): UpsertResult
    {
        $request = $this->withDefaultNamespaceForUpsert($request);
        $response = $this->transport->postJson(
            $this->dataBaseUrl,
            '/vectors/upsert',
            $request->toApiBody()
        );
        $data = Json::decodeObject($response->getBody()->getContents());
        $count = isset($data['upsertedCount']) && is_numeric($data['upsertedCount'])
            ? (int) $data['upsertedCount']
            : count($request->vectors);

        return new UpsertResult($count);
    }

    public function query(QueryVectorsRequest $request): QueryVectorsResult
    {
        $request = $this->withDefaultNamespaceForQuery($request);
        $response = $this->transport->postJson(
            $this->dataBaseUrl,
            '/query',
            $request->toApiBody()
        );

        return $this->parseQueryResponse($response);
    }

    public function delete(DeleteVectorsRequest $request): void
    {
        $request = $this->withDefaultNamespaceForDelete($request);
        $this->transport->postJson(
            $this->dataBaseUrl,
            '/vectors/delete',
            $request->toApiBody()
        );
    }

    public function describeIndexStats(?string $namespace = null): DescribeIndexStatsResult
    {
        // Do not apply connection default namespace: Pinecone treats `filter` as metadata filter;
        // serverless indexes often reject filtered stats, and defaults would break sync/health jobs.
        $body = [];
        if ($namespace !== null && $namespace !== '') {
            $body['filter'] = ['namespace' => $namespace];
        }
        $response = $this->transport->postJson(
            $this->dataBaseUrl,
            '/describe_index_stats',
            $body
        );

        return $this->parseStatsResponse($response);
    }

    private function parseQueryResponse(ResponseInterface $response): QueryVectorsResult
    {
        $data = Json::decodeObject($response->getBody()->getContents());
        $matches = [];
        if (isset($data['matches']) && is_array($data['matches'])) {
            foreach ($data['matches'] as $m) {
                if (! is_array($m) || ! isset($m['id']) || ! is_string($m['id'])) {
                    continue;
                }
                $score = isset($m['score']) && is_numeric($m['score']) ? (float) $m['score'] : 0.0;
                $values = isset($m['values']) && is_array($m['values']) ? array_map('floatval', $m['values']) : null;
                $meta = isset($m['metadata']) && is_array($m['metadata']) ? $m['metadata'] : null;
                $matches[] = new QueryVectorMatch($m['id'], $score, $values, $meta);
            }
        }
        $ns = isset($data['namespace']) && is_string($data['namespace']) ? $data['namespace'] : null;
        $usage = isset($data['usage']) && is_array($data['usage']) ? $data['usage'] : null;

        return new QueryVectorsResult($matches, $ns, $usage);
    }

    private function parseStatsResponse(ResponseInterface $response): DescribeIndexStatsResult
    {
        $data = Json::decodeObject($response->getBody()->getContents());
        $dim = isset($data['dimension']) && is_numeric($data['dimension']) ? (int) $data['dimension'] : 0;
        $total = isset($data['totalVectorCount']) && is_numeric($data['totalVectorCount'])
            ? (int) $data['totalVectorCount'] : 0;
        $metric = isset($data['metric']) && is_string($data['metric']) ? $data['metric'] : null;

        $namespaces = [];
        if (isset($data['namespaces']) && is_array($data['namespaces'])) {
            foreach ($data['namespaces'] as $key => $entry) {
                if (is_array($entry) && isset($entry['vectorCount']) && is_numeric($entry['vectorCount'])) {
                    $name = is_string($key) ? $key : '';
                    $namespaces[$name] = new NamespaceSummary($name, (int) $entry['vectorCount']);
                }
            }
        }

        return new DescribeIndexStatsResult($dim, $total, $namespaces, $metric);
    }

    /**
     * For upsert/query/delete only. `null` means “unspecified” → apply connection default.
     * Any non-null value (including '') is caller intent: '' targets Pinecone’s default namespace (omit JSON key).
     */
    private function resolveNamespaceForMutatingOperations(?string $explicit): ?string
    {
        if ($explicit !== null) {
            return $explicit;
        }

        return $this->defaultNamespace;
    }

    private function withDefaultNamespaceForUpsert(UpsertVectorsRequest $request): UpsertVectorsRequest
    {
        $ns = $this->resolveNamespaceForMutatingOperations($request->namespace);
        if ($ns === $request->namespace) {
            return $request;
        }

        return new UpsertVectorsRequest($request->vectors, $ns);
    }

    private function withDefaultNamespaceForQuery(QueryVectorsRequest $request): QueryVectorsRequest
    {
        $ns = $this->resolveNamespaceForMutatingOperations($request->namespace);
        if ($ns === $request->namespace) {
            return $request;
        }

        return new QueryVectorsRequest(
            $request->vector,
            $request->topK,
            $ns,
            $request->filter,
            $request->includeMetadata,
            $request->includeValues,
            $request->queryByVectorId,
        );
    }

    private function withDefaultNamespaceForDelete(DeleteVectorsRequest $request): DeleteVectorsRequest
    {
        $ns = $this->resolveNamespaceForMutatingOperations($request->namespace);
        if ($ns === $request->namespace) {
            return $request;
        }

        return new DeleteVectorsRequest(
            namespace: $ns,
            ids: $request->ids,
            filter: $request->filter,
            deleteAll: $request->deleteAll,
        );
    }
}
