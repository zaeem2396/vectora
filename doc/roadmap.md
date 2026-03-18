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

| Module | Sub-module / Task                              | Status         |
| ------ | ---------------------------------------------- | -------------- |
| Core   | `VectorStoreContract` + Pinecone implementation | 🟡 In Progress |
| Core   | DTOs (upsert, query, delete, index ops)        | 🟡 In Progress |
| Core   | PSR-18 HTTP transport + retries                | 🟡 In Progress |
| Core   | Exponential backoff + 429 / `Retry-After`      | 🟡 In Progress |
| Core   | Observability hooks (request/error callbacks)  | 🟡 In Progress |
| Core   | `IndexAdminContract` (control plane)           | 🟡 In Progress |
| Core   | Configurable API version + index host          | 🟡 In Progress |
| Core   | PHPUnit coverage for client + retry            | 🟡 In Progress |

---

## Phase 2 — Laravel Integration

| Module  | Sub-module / Task                         | Status  |
| ------- | ----------------------------------------- | ------- |
| Laravel | `config/pinecone.php` (publishable)       | 🔴 Pending |
| Laravel | Service Provider + Facade                 | 🔴 Pending |
| Laravel | Queue jobs (single + batch upsert/delete) | 🔴 Pending |
| Laravel | Events (`VectorSynced`, `VectorFailed`)   | 🔴 Pending |
| Laravel | Artisan commands (`pinecone:sync`, …)       | 🔴 Pending |
| Laravel | Auto-discovery support                    | 🔴 Pending |
| Laravel | Env-based multi-index config              | 🔴 Pending |

---

## Phase 3 — Embeddings

| Module     | Sub-module / Task              | Status  |
| ---------- | ------------------------------ | ------- |
| Embeddings | `EmbeddingDriver` interface    | 🔴 Pending |
| Embeddings | OpenAI driver                  | 🔴 Pending |
| Embeddings | Stub / array driver (testing)  | 🔴 Pending |
| Embeddings | Custom driver docs             | 🔴 Pending |
| Embeddings | Batch embedding support        | 🔴 Pending |
| Embeddings | Embedding caching (hash dedup) | 🔴 Pending |

---

## Phase 4 — Eloquent Integration

| Module   | Sub-module / Task                    | Status  |
| -------- | ------------------------------------ | ------- |
| Eloquent | `HasEmbeddings` trait                | 🔴 Pending |
| Eloquent | Auto-sync on create/update/delete    | 🔴 Pending |
| Eloquent | Sync modes (sync vs queued)          | 🔴 Pending |
| Eloquent | Semantic search API                  | 🔴 Pending |
| Eloquent | Batch indexing API                   | 🔴 Pending |
| Eloquent | Soft delete handling                 | 🔴 Pending |
| Eloquent | Selective field indexing             | 🔴 Pending |

---

## Phase 5 — DX & Hardening

| Module | Sub-module / Task              | Status  |
| ------ | ------------------------------ | ------- |
| DX     | Query result caching           | 🔴 Pending |
| DX     | Full PHPUnit coverage          | 🔴 Pending |
| DX     | Root README with examples      | 🔴 Pending |
| DX     | Error classification           | 🔴 Pending |
| DX     | Developer debug mode           | 🔴 Pending |
| DX     | Config validation              | 🔴 Pending |

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
