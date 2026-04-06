# Laravel integration (Phase 2)

First-time setup: **[installation.md](./installation.md)** (Composer, `.env`, publish config).

## Service provider & discovery

The package registers `PineconeServiceProvider` and the **`Pinecone`** / **`Vector`** facades via `composer.json` `extra.laravel` (auto-discovery).

Publish config:

```bash
php artisan vendor:publish --tag=pinecone-config
```

## Vector store drivers (Phase 7)

`pinecone.vector_store.default` selects the active **`VectorStoreContract`** implementation (`pinecone`, `memory`, `sqlite`, `qdrant`, `weaviate`, `pgvector`). Override per Eloquent model with **`Embeddable::vectorEmbeddingStoreDriver()`**. Inject **`VectorStoreManager`** to call **`driver(?string $name, ?string $pineconeIndex)`** explicitly.

**`Pinecone::admin()`** is only available when the default driver is **`pinecone`** (control plane is Pinecone-specific).

See **[multi-backend.md](./multi-backend.md)** for env vars and driver behaviour.

## RAG / LLM (Phase 8)

`pinecone.llm.default` selects **`LLMDriver`** (`stub`, `openai`). Use **`Vector::using(YourModel::class)->ask('…')`** or **`YourModel::rag()->ask('…')`** on `Embeddable` models. Inject **`LLMManager`** for `driver(?string $name)`.

See **[rag.md](./rag.md)** for streaming, metadata filters, and optional **`ConversationMemory`**.

## Multi-index configuration

`config/pinecone.php` supports named connections under `indexes`, with `default` selecting which name `Pinecone::connection()` uses when no argument is passed.

Legacy single-host apps can keep using top-level `host` / `namespace`; when `indexes` is empty they are mapped to the connection named by `default` (`PINECONE_INDEX`), not hard-coded to the string `default`.

Each index’s `namespace` value is passed into `PineconeVectorStore` as the **default namespace** for **upsert / query / delete**: when the request or job passes `namespace: null`, that default is used. Pass an **empty string** `''` to target Pinecone’s default namespace (no `namespace` in the API payload) instead of the connection default. **`describeIndexStats()`**, **`pinecone:sync`**, and **`DescribeIndexStatsJob`** only request **unfiltered** index stats (Pinecone’s stats `filter` is metadata-oriented; serverless indexes reject filtered stats). Use the `namespaces` breakdown in the response for per-namespace counts.

## Queue

Set `PINECONE_QUEUE_CONNECTION` to route jobs to a given connection. Leave **`PINECONE_QUEUE` unset** so jobs use that connection’s **default queue name** (instead of always pushing to Laravel’s global `default` queue).

## HTTP client

The Laravel integration uses **Guzzle** as the PSR-18 client (`guzzlehttp/guzzle` is a **runtime** `require` so production `composer install --no-dev` works).

## Facade & container

- `Pinecone::connection(?string $index)` → `VectorStoreContract` (Pinecone data plane when that is the resolved backend; see multi-backend docs)
- `Pinecone::admin()` → `IndexAdminContract` (requires default vector store driver `pinecone`)
- `Pinecone::embeddings(?string $driver)` / `embed()` / `embedMany()` → `EmbeddingDriver` (Phase 3)
- Type-hint `VectorStoreContract` / `VectorStoreManager` / `IndexAdminContract` / `EmbeddingDriver` / `LLMDriver` / `LLMManager` / `PineconeClientFactory` in your own services.

See **[embeddings.md](./embeddings.md)** for drivers, OpenAI env vars, and optional result caching.

## Eloquent

Phase 4: **`HasEmbeddings`** on models implementing **`Embeddable`**, with **`SyncModelEmbeddingJob`** for queued upserts. See **[eloquent.md](./eloquent.md)**.

## Queue jobs

| Job | Purpose |
| --- | --- |
| `UpsertVectorsJob` | Async upsert with array payloads |
| `DeleteVectorsJob` | Async delete by ids, filter, or `deleteAll` |
| `SyncModelEmbeddingJob` | Queued upsert for `HasEmbeddings` models |
| `DescribeIndexStatsJob` | Async stats / health |

Jobs honour `pinecone.queue.connection` and `pinecone.queue.queue`. Successful operations dispatch `VectorSynced`; failures dispatch `VectorFailed` before rethrowing.

## Artisan

| Command | Purpose |
| ------- | ------- |
| `pinecone:sync` | Print `describe_index_stats` (connectivity / counts) |
| `pinecone:flush` | `deleteAll` for a namespace (`--force` required in production) |

## HTTP & logging

`PineconeClientFactory` builds a shared `PineconeHttpTransport` (Guzzle PSR-18 client) using `pinecone.http.*`. Set `PINECONE_LOG_REQUESTS=true` to emit debug lines via Laravel’s logger (optional channel via `PINECONE_LOG_CHANNEL`).

**Debug:** `PINECONE_DEBUG=true` adds truncated request/response body previews (`pinecone.debug.*`). **Query cache:** `PINECONE_QUERY_CACHE=true` wraps the vector store so `query()` hits Laravel cache (see **`[dx.md](./dx.md)`**). Config is validated on provider boot (timeouts, eloquent sync mode, query-cache TTL).

**Metrics (Phase 6):** `PINECONE_METRICS=true` dispatches **`PineconeHttpRequestFinished`** after each logical Pinecone HTTP call (duration, status, correlation id). See **`[observability.md](./observability.md)`**.
