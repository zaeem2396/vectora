# 🚀 Vectora — Roadmap (Laravel + Pinecone)

## Status Legend

- 🟢 Done
- 🟡 In Progress
- 🔴 Pending

---

## Phase 0 — Foundation

| Module     | Sub-module / Task                         | Status     |
| ---------- | ----------------------------------------- | ---------- |
| Foundation | Architecture doc (`doc/readme.md`)        | 🟢 Done    |
| Foundation | Repository layout (`src/*`, `config/`, …) | 🟢 Done    |
| Foundation | `composer.json`, PHPUnit, Testbench       | 🟢 Done    |
| Foundation | CI setup (GitHub Actions)                 | 🟢 Done    |
| Foundation | Coding standards (Pint / PHPStan)         | 🟢 Done    |

---

## Phase 1 — Core (Framework Agnostic)

| Module | Sub-module / Task                              | Status  |
| ------ | ---------------------------------------------- | ------- |
| Core   | `VectorStoreContract` + Pinecone implementation | 🟢 Done |
| Core   | DTOs (upsert, query, delete, index ops)        | 🟢 Done |
| Core   | PSR-18 HTTP transport + retries                | 🟢 Done |
| Core   | Exponential backoff + 429 / `Retry-After`      | 🟢 Done |
| Core   | Observability hooks (request/error callbacks)  | 🟢 Done |
| Core   | `IndexAdminContract` (control plane)           | 🟢 Done |
| Core   | Configurable API version + index host          | 🟢 Done |
| Core   | PHPUnit coverage for client + retry            | 🟢 Done |

---

## Phase 2 — Laravel Integration

Status: **complete** (see `doc/laravel.md`).

| Module  | Sub-module / Task                         | Status  |
| ------- | ----------------------------------------- | ------- |
| Laravel | `config/pinecone.php` (publishable)       | 🟢 Done |
| Laravel | Service Provider + Facade                 | 🟢 Done |
| Laravel | Queue jobs (single + batch upsert/delete) | 🟢 Done |
| Laravel | Events (`VectorSynced`, `VectorFailed`)   | 🟢 Done |
| Laravel | Artisan commands (`pinecone:sync`, …)     | 🟢 Done |
| Laravel | Auto-discovery support                    | 🟢 Done |
| Laravel | Env-based multi-index config              | 🟢 Done |

---

## Phase 3 — Embeddings

Status: **complete** (see `doc/embeddings.md`).

| Module     | Sub-module / Task              | Status  |
| ---------- | ------------------------------ | ------- |
| Embeddings | `EmbeddingDriver` interface    | 🟢 Done |
| Embeddings | OpenAI driver                  | 🟢 Done |
| Embeddings | Stub / array driver (testing)  | 🟢 Done |
| Embeddings | Custom driver docs             | 🟢 Done |
| Embeddings | Batch embedding support        | 🟢 Done |
| Embeddings | Embedding caching (hash dedup) | 🟢 Done |

---

## Phase 4 — Eloquent Integration

Status: **complete** (see `doc/eloquent.md`).

| Module   | Sub-module / Task                    | Status  |
| -------- | ------------------------------------ | ------- |
| Eloquent | `HasEmbeddings` trait                | 🟢 Done |
| Eloquent | Auto-sync on create/update/delete    | 🟢 Done |
| Eloquent | Sync modes (sync vs queued)          | 🟢 Done |
| Eloquent | Semantic search API                  | 🟢 Done |
| Eloquent | Batch indexing API                   | 🟢 Done |
| Eloquent | Soft delete handling                 | 🟢 Done |
| Eloquent | Selective field indexing             | 🟢 Done |

---

## Phase 5 — DX & Hardening

Status: **complete** (see `doc/dx.md`).

| Module | Sub-module / Task              | Status  |
| ------ | ------------------------------ | ------- |
| DX     | Query result caching           | 🟢 Done |
| DX     | Full PHPUnit coverage          | 🟢 Done |
| DX     | Root README with examples      | 🟢 Done |
| DX     | Error classification           | 🟢 Done |
| DX     | Developer debug mode           | 🟢 Done |
| DX     | Config validation              | 🟢 Done |

---

## Phase 6 — Observability

| Module         | Sub-module / Task | Status  |
| -------------- | ----------------- | ------- |
| Observability  | Metrics / tracing | 🔴 Pending |

---

## Future / Bonus

| Module        | Sub-module / Task           | Status  |
| ------------- | --------------------------- | ------- |
| Multi-backend | Qdrant / Weaviate           | 🔴 Pending |
| Abstraction   | Scout-style engine          | 🔴 Pending |
| Search        | Hybrid search               | 🔴 Pending |
| AI            | RAG helpers                 | 🔴 Pending |

---

## Versioning

Follow **SemVer**: breaking changes to contracts/config → major bump.
