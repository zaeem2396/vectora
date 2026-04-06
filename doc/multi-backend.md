# Multi-backend vector stores (Phase 7)

Vectora routes **`VectorStoreContract`** through **`VectorStoreManager`**, configured under **`pinecone.vector_store`** in `config/pinecone.php`. The default driver is **`pinecone`** (existing HTTP data plane). Alternate drivers are useful for **local tests**, **CI**, and **self-hosted** vector databases.

---

## Configuration

| Env / key | Purpose |
|-----------|---------|
| **`VECTORA_VECTOR_STORE_DRIVER`** | `pinecone` (default), `memory`, `sqlite`, `qdrant`, `weaviate`, `pgvector` (matched **case-insensitively**; config and `Embeddable::vectorEmbeddingStoreDriver()` are normalized to lowercase) |
| `VECTORA_SQLITE_VECTOR_DATABASE` | SQLite path or `:memory:` |
| `VECTORA_SQLITE_VECTOR_TABLE` | Table name (default `vectora_vectors`) |
| `VECTORA_QDRANT_URL` | Qdrant REST base URL |
| `VECTORA_QDRANT_API_KEY` | Optional API key header |
| `VECTORA_QDRANT_COLLECTION` | Collection name (unnamed single vector per collection) |
| `VECTORA_WEAVIATE_URL` | Weaviate REST URL |
| `VECTORA_WEAVIATE_API_KEY` | Optional bearer token |
| `VECTORA_WEAVIATE_CLASS` | Weaviate class (vectorizer: none; vectors supplied at upsert) |
| `VECTORA_PGVECTOR_CONNECTION` | Laravel DB connection name (empty = default) |
| `VECTORA_PGVECTOR_TABLE` | Table name |
| `VECTORA_PGVECTOR_DIMENSIONS` | Fixed embedding width (must match your embedding model) |
| `VECTORA_PGVECTOR_ENSURE_SCHEMA` | When true, runs `CREATE EXTENSION vector` / `CREATE TABLE` on first use (dev only) |

**Container binding:** `VectorStoreContract` resolves to **`VectorStoreManager::driver()`** (default driver). **`Pinecone::connection($index)`** still returns a Pinecone-backed store when the default driver is `pinecone`; with other defaults, use **`VectorStoreManager::driver('pinecone', $index)`** for explicit Pinecone.

**Query cache:** `PINECONE_QUERY_CACHE` applies to **non-Pinecone** drivers via the manager (Pinecone continues to use the factory’s existing wrapping to avoid double caching).

---

## Eloquent per-model driver

Implement **`Embeddable::vectorEmbeddingStoreDriver(): ?string`**. Return a driver key (e.g. `memory`) or **`null`** for the global default. **`AbstractEmbeddableModel`** defaults to `null`.

**`DeleteVectorsJob`** records the optional driver so queued deletes hit the same backend as the model.

---

## Capability introspection

Stores that implement **`ProvidesVectorStoreCapabilities`** expose **`vectorStoreCapabilities(): VectorStoreCapabilities`** (`backendName`, namespace / metadata / filter-delete / stats flags). Use this for conditional UI or tests.

---

## Operational notes

| Driver | Notes |
|--------|--------|
| **memory** | In-process singleton; cleared when the PHP process ends. |
| **sqlite** | File or `:memory:`; cosine similarity computed in PHP per query. |
| **qdrant** | Logical ids are hashed per namespace; payload keys `_vectora_ns`, `_vectora_pid`. |
| **weaviate** | Properties `vectoraNs`, `vectoraMeta` (JSON metadata); GraphQL `nearVector` + PHP-side metadata filter refinement. |
| **pgvector** | Requires **`pgsql`**, extension **`vector`**, and matching **`dimensions`**. |

---

## Pinecone control plane

**`Pinecone::admin()`** (index create/describe/delete) requires **`pinecone.vector_store.default`** to be **`pinecone`**. If you run another default driver, resolve **`IndexAdminContract`** only when you still configure a Pinecone API key and temporarily switch driver or call the factory in your own service.

---

**Phase 9 ingestion** (`Vector::ingest()->syncUpsert()`) resolves vectors through **`VectorStoreManager::driver()`**, so the same multi-backend rules apply as for Eloquent sync.

## Further reading

- **[laravel.md](./laravel.md)** — facade, jobs, manager usage  
- **[eloquent.md](./eloquent.md)** — `HasEmbeddings`, sync modes  
- **[core.md](./core.md)** — framework-agnostic `VectorStoreContract` usage  
- **[ingestion.md](./ingestion.md)** — chunking, extractors, `IngestUpsertJob`  
