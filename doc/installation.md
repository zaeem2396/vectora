# Installation

This guide covers installing **Vectora** (`vectora/laravel-pinecone`) in a Laravel application and the minimum configuration to call Pinecone.

---

## 1. Prerequisites

| Requirement | Notes |
|-------------|--------|
| PHP | **8.2+** with `ext-json` |
| Laravel | **11.x** or **12.x** |
| Pinecone | API key and index **host** (from the Pinecone console) |
| Queue (optional) | Redis, database, or other Laravel queue driver if you use **queued** jobs or `PINECONE_ELOQUENT_SYNC=queued` |

---

## 2. Install via Composer

### From Packagist (when published)

```bash
composer require vectora/laravel-pinecone
```

### From GitHub (VCS) before Packagist

Add a VCS repository and require the branch you track (often `main`):

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/cod3xa/vectora.git"
        }
    ],
    "require": {
        "vectora/laravel-pinecone": "dev-main"
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

Then:

```bash
composer update vectora/laravel-pinecone
```

---

## 3. Laravel integration

1. **Auto-discovery** — The package registers `PineconeServiceProvider` and the **`Pinecone`** facade via `composer.json` `extra.laravel`. No manual `config/app.php` entry is required unless you disable package discovery.

2. **Publish config** (recommended):

   ```bash
   php artisan vendor:publish --tag=pinecone-config
   ```

   This copies `config/pinecone.php` into your app so you can rely on `.env` without editing vendor files.

---

## 4. Environment variables

At minimum, set:

| Variable | Purpose |
|----------|---------|
| `PINECONE_API_KEY` | Pinecone API key |
| `PINECONE_HOST` | Data-plane host for your index (or configure `indexes` in config) |

Common optional keys (see published `config/pinecone.php` for the full list):

| Variable | Purpose |
|----------|---------|
| `PINECONE_INDEX` | Default logical index name when using named `indexes` |
| `PINECONE_NAMESPACE` | Default namespace for upsert/query/delete |
| `PINECONE_API_VERSION` | `X-Pinecone-Api-Version` header (default aligned with package) |
| `PINECONE_ELOQUENT_SYNC` | `sync` or `queued` for `HasEmbeddings` models |
| `PINECONE_EMBEDDING_DRIVER` | `deterministic` (dev) or `openai` |
| `OPENAI_API_KEY` | For OpenAI embeddings when using the `openai` driver |
| `PINECONE_QUERY_CACHE` | Cache `query()` results via Laravel cache (see **[dx.md](./dx.md)**) |
| `PINECONE_DEBUG` | Verbose truncated HTTP body logging (development only) |
| `PINECONE_METRICS` | Dispatch `PineconeHttpRequestFinished` per HTTP call (see **[observability.md](./observability.md)**) |
| `VECTORA_VECTOR_STORE_DRIVER` | Default vector backend: `pinecone`, `memory`, `sqlite`, `qdrant`, `weaviate`, `pgvector` (see **[multi-backend.md](./multi-backend.md)**) |

**Embeddings:** see **[embeddings.md](./embeddings.md)** for drivers and cache-related env vars.

**Alternate vector stores:** see **[multi-backend.md](./multi-backend.md)** for Qdrant, Weaviate, SQLite, and pgvector env keys.

**DX / hardening:** see **[dx.md](./dx.md)** for query cache keys, debug options, config validation, and `ApiException` classification.

**Observability:** see **[observability.md](./observability.md)** for metrics events and correlation ids.

---

## 5. Verify connectivity

With config and env in place:

```bash
php artisan pinecone:sync
```

This exercises the control/data plane wiring (see **[laravel.md](./laravel.md)**). Ensure your index host and API key match the environment you expect.

---

## 6. Queues

If you dispatch `UpsertVectorsJob`, `DeleteVectorsJob`, `SyncModelEmbeddingJob`, or use Eloquent **`queued`** sync mode, run a worker:

```bash
php artisan queue:work
```

Optional: `PINECONE_QUEUE_CONNECTION` and `PINECONE_QUEUE` in `.env` (see **[laravel.md](./laravel.md)**).

---

## 7. Next steps

| Goal | Read |
|------|------|
| Facade, jobs, commands, multi-index | [laravel.md](./laravel.md) |
| Text → vectors (OpenAI, cache) | [embeddings.md](./embeddings.md) |
| Model sync + semantic search | [eloquent.md](./eloquent.md) |
| Low-level HTTP / contracts | [core.md](./core.md) |
| Multi-backend (non-Pinecone indexes) | [multi-backend.md](./multi-backend.md) |

---

## 8. Troubleshooting

| Symptom | Check |
|---------|--------|
| `Pinecone api_key is not configured` | `PINECONE_API_KEY` in `.env`, config cached with `php artisan config:clear` |
| `host is not configured for index` | `PINECONE_HOST` or `indexes.*.host` for the connection name you use |
| Vectors never appear | Queue worker running for queued mode; correct namespace; upsert not failing silently (check logs / `VectorFailed` event) |
| OpenAI errors | `OPENAI_API_KEY`, model name, and `PINECONE_EMBEDDING_DRIVER=openai` |
| `Pinecone::admin` fails with vector_store message | Default driver is not `pinecone`; set `VECTORA_VECTOR_STORE_DRIVER=pinecone` or use only data-plane APIs |

---

## 9. Running this package’s test suite (contributors)

Contributors cloning the library need **Composer** and, for Eloquent feature tests, the **pdo_sqlite** PHP extension (see CI workflow). From the package root:

```bash
composer install
composer test
```
