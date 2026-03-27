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
