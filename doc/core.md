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

- `Vectora\Pinecone\Contracts\VectorStoreContract` — vector operations; implementations include **`PineconeVectorStore`**, **`LocalMemoryVectorStore`**, **`SqliteVectorStore`**, **`QdrantVectorStore`**, **`WeaviateVectorStore`**, **`PgVectorVectorStore`** (see **[multi-backend.md](./multi-backend.md)**).
- `Vectora\Pinecone\Contracts\ProvidesVectorStoreCapabilities` — optional capability advertisement for a store.
- `Vectora\Pinecone\Contracts\IndexAdminContract` — index lifecycle.
- `Vectora\Pinecone\Contracts\RagRetrieverContract` / **`LLMDriver`** — RAG retrieval and chat completion (Phase 8; see **[rag.md](./rag.md)**).
- `Vectora\Pinecone\Contracts\TextChunkingStrategy` / **`TextExtractor`** — document splitting and file extraction (Phase 9; see **[ingestion.md](./ingestion.md)**).
- **`RerankerContract`** — post-query reordering (Phase 10; see **[search.md](./search.md)**).

## Observability

Pass `ObservabilityHooks` into `PineconeHttpTransport` for before/after/error callbacks without coupling to PSR-3.

Optional **`PineconeMetrics`** (last constructor argument): records one outcome per logical HTTP call (after retries) with a correlation id. Use **`NullPineconeMetrics`** or omit (defaults to no metrics). See **`[observability.md](./observability.md)`** for Laravel event wiring.
