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

$store = new PineconeVectorStore($transport, getenv('PINECONE_HOST'), 'optional-default-namespace');
$admin = new PineconeIndexAdmin($transport);
```

Optional **default namespace** (third constructor argument): when upsert/query/delete omit `namespace` (`null`), the store applies this value. A non-null `namespace` of `''` means “Pinecone default namespace” (no `namespace` field in the JSON) and is **not** replaced by the connection default. **`describeIndexStats()`** is always unfiltered index-wide stats (Pinecone does not support reliable namespace-only totals via this API on serverless).

- **Data plane** (upsert/query/delete/stats): index host from the Pinecone console.
- **Control plane** (create/describe/delete index): defaults to `https://api.pinecone.io`.

## Contracts

- `Vectora\Pinecone\Contracts\VectorStoreContract` — vector operations (swappable backend later).
- `Vectora\Pinecone\Contracts\IndexAdminContract` — index lifecycle.

## Observability

Pass `ObservabilityHooks` into `PineconeHttpTransport` for before/after/error callbacks without coupling to PSR-3.
