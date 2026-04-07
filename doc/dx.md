# Developer experience & hardening

This page summarizes **Phase 5** features (query caching, HTTP debug logging, config validation, **`ApiException`**) and **Phase 11** (semantic Eloquent builder, **`#[EmbeddingColumns]`**, Artisan commands, clearer semantic errors).

---

## Query result cache

Vector similarity queries can be expensive. When **`PINECONE_QUERY_CACHE=true`**, `VectorStoreContract::query()` responses are cached in Laravel’s cache store (same subsystem as embedding cache, but separate keys).

| Env / config | Purpose |
|----------------|---------|
| `PINECONE_QUERY_CACHE` | Enable (`true` / `false`) |
| `PINECONE_QUERY_CACHE_STORE` | Optional named cache store |
| `PINECONE_QUERY_CACHE_PREFIX` | Key prefix (default `vectora.pinecone.query`) |
| `PINECONE_QUERY_CACHE_TTL` | Seconds; omit or empty for **forever** (until manual flush) |

Cache keys hash the normalized Pinecone query body plus an index fingerprint (logical index name, host, default namespace). After **upserts** or **deletes**, cached query rows may be stale until TTL expiry or **`php artisan cache:clear`**.

---

## Developer debug mode

When **`PINECONE_DEBUG=true`**, the HTTP client logs **truncated** request and response bodies at **debug** level (`pinecone.debug.request` / `pinecone.debug.response`). Use alongside **`PINECONE_LOG_REQUESTS`** for URI/method/status line logging.

| Env | Purpose |
|-----|---------|
| `PINECONE_DEBUG` | Enable body previews |
| `PINECONE_DEBUG_CHANNEL` | Optional log channel |
| `PINECONE_DEBUG_BODY_MAX` | Max characters per preview (default 2048) |

Do not enable debug logging in production for sensitive payloads.

---

## Config validation

On **`PineconeServiceProvider::boot()`**, **`PineconeConfigValidator`** checks:

- HTTP timeouts and retry counts are positive / sensible
- `pinecone.eloquent.default_sync` is `sync` or `queued`
- When query cache is enabled, `ttl` is not negative

Invalid configuration throws **`InvalidArgumentException`** early so misconfiguration fails fast.

---

## Error classification

**`ApiException`** (thrown after retries on HTTP failures) exposes:

- **`category(): ApiErrorCategory`** — `rate_limited`, `authentication`, `not_found`, `bad_request`, `client`, `server`, or `unknown`
- **`isAuthenticationError()`**, **`isNotFound()`**, **`isClientError()`**, **`isServerError()`**, plus existing **`isRateLimited()`**

Use these in listeners or `VectorFailed` handlers to branch on failure type without parsing raw status codes everywhere.

---

## Phase 11 — DX 2.0 (semantic Eloquent, attributes, casts, artisan)

Phase 11 builds on **`HasEmbeddings`**: a **`SemanticEloquentBuilder`** (via `Model::query()` on embeddable models), **PHP 8 attributes** for embedding columns and optional index name, a **concatenation cast** for virtual embedding text, Artisan **`make:vector-model`** / **`pinecone:semantic-debug`**, **`pinecone.dx`** config, and **`SemanticSearchInvalidArgumentException`** for clearer invalid-argument failures.

### Semantic query builder

On **`Embeddable`** models, `newQuery()` returns **`SemanticEloquentBuilder`**:

- **`semanticWhere($queryText, $topK = 10, $additionalFilter = null)`** — runs the same vector query as **`Embeddable::semanticSearch()`**, restricts SQL rows with **`WHERE id IN (...)`**, and orders by similarity (MySQL **`FIELD()`**; other drivers use a **`CASE`** expression).
- **`semanticOrderBy(...)`** — if the last **`semanticWhere`** used the same text, topK, and filter, **re-applies** similarity ordering only (no second vector API call). Otherwise it behaves like **`semanticWhere`**.

Compose with normal Eloquent **`where`**: `Article::query()->where('published', true)->semanticWhere('Laravel queues', 20)`.

Calling **`semanticOrderBy`** after a matching **`semanticWhere`** uses **`reorder()`** before applying similarity order, which clears prior **`orderBy`** clauses — put **`semanticWhere`** first, then add secondary ordering if needed.

### Attributes and defaults

- **`#[EmbeddingColumns(columns: ['title', 'body'])]`** on the model class — **HasEmbeddings** resolves **`vectorEmbeddingFields()`** from this attribute when you do **not** override **`vectorEmbeddingFields()`** manually.
- **`#[VectorEmbeddingIndexName('logical-index')]`** — optional; feeds **`vectorEmbeddingIndex()`** when not overridden.

### Concat embedding cast

**`ConcatEmbeddingTextCast`** implements **`Castable`** and **`CastsAttributes`**: use a virtual column in **`$casts`** (e.g.  `'embedding_text' => ConcatEmbeddingTextCast::class.':title,body'`) and point **`vectorEmbeddingFields()`** at **`embedding_text`** only.

