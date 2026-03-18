<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Core\Pinecone;

use Vectora\Pinecone\Contracts\IndexAdminContract;
use Vectora\Pinecone\Core\Http\Json;
use Vectora\Pinecone\Core\Http\PineconeHttpTransport;
use Vectora\Pinecone\DTO\CreateIndexRequest;
use Vectora\Pinecone\DTO\IndexDescriptionResult;

final class PineconeIndexAdmin implements IndexAdminContract
{
    private const DEFAULT_CONTROL_PLANE = 'https://api.pinecone.io';

    private readonly string $controlBaseUrl;

    public function __construct(
        private readonly PineconeHttpTransport $transport,
        ?string $controlPlaneBaseUrl = null,
    ) {
        $this->controlBaseUrl = PineconeHttpTransport::normalizeBaseUrl(
            $controlPlaneBaseUrl ?? self::DEFAULT_CONTROL_PLANE
        );
    }

    public function createIndex(CreateIndexRequest $request): void
    {
        $this->transport->postJson(
            $this->controlBaseUrl,
            '/indexes',
            $request->toApiBody()
        );
    }

    public function deleteIndex(string $name): void
    {
        $path = '/indexes/'.rawurlencode($name);
        $this->transport->delete($this->controlBaseUrl, $path);
    }

    public function describeIndex(string $name): IndexDescriptionResult
    {
        $path = '/indexes/'.rawurlencode($name);
        $response = $this->transport->get($this->controlBaseUrl, $path);
        $data = Json::decodeObject($response->getBody()->getContents());
        $indexName = isset($data['name']) && is_string($data['name']) ? $data['name'] : $name;
        $status = isset($data['status']) && is_string($data['status']) ? $data['status'] : 'unknown';

        return new IndexDescriptionResult($indexName, $status, $data);
    }
}
