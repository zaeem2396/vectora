# Core layer (Phase 1)

Framework-agnostic Pinecone access via **PSR-18**.

## Building clients

```php
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Vectora\Pinecone\Core\Http\PineconeHttpTransport;
use Vectora\Pinecone\Core\Http\RetryPolicy;
use Vectora\Pinecone\Core\Pinecone\PineconeVectorStore;
use Vectora\Pinecone\Core\Pinecone\PineconeIndexAdmin;

$http = new Client(['timeout' => 30]);
$f = new HttpFactory();
$transport = new PineconeHttpTransport(
    $http,
    $f,
    $f,
    getenv('PINECONE_API_KEY'),
    '2025-10',
    new RetryPolicy(),
);

$store = new PineconeVectorStore($transport, getenv('PINECONE_HOST'));
$admin = new PineconeIndexAdmin($transport);
```

- **Data plane** (upsert/query/delete/stats): index host from the Pinecone console.
- **Control plane** (create/describe/delete index): defaults to `https://api.pinecone.io`.

## Contracts

- `Vectora\Pinecone\Contracts\VectorStoreContract` — vector operations (swappable backend later).
- `Vectora\Pinecone\Contracts\IndexAdminContract` — index lifecycle.

## Observability

Pass `ObservabilityHooks` into `PineconeHttpTransport` for before/after/error callbacks without coupling to PSR-3.
