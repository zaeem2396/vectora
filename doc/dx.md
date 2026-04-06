# Developer experience & hardening

This page summarizes **Phase 5** features: query caching, HTTP debug logging, config validation at boot, and **`ApiException`** error classification for application-level handling.

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

## Related docs

- **[installation.md](./installation.md)** — environment variables
- **[laravel.md](./laravel.md)** — service provider, facade, jobs
- **[ingestion.md](./ingestion.md)** — Phase 9 `Vector::ingest()` and chunk defaults
- **[observability.md](./observability.md)** — Phase 6 HTTP metrics and `PineconeHttpRequestFinished`
- **[roadmap.md](./roadmap.md)** — phase status
