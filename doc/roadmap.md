# 🚀 Vectora — Roadmap (Laravel + Pinecone)

## Status Legend

* 🟢 Done
* 🟡 In Progress
* 🔴 Pending

---

## Phase 0 — Foundation

| Module     | Sub-module / Task                         | Status  |
| ---------- | ----------------------------------------- | ------- |
| Foundation | Architecture doc (`doc/readme.md`)        | 🟢 Done |
| Foundation | Repository layout (`src/*`, `config/`, …) | 🟢 Done |
| Foundation | `composer.json`, PHPUnit, Testbench       | 🟢 Done |
| Foundation | CI setup (GitHub Actions)                 | 🟢 Done |
| Foundation | Coding standards (Pint / PHPStan)         | 🟢 Done |

---

## Phase 1 — Core (Framework Agnostic)

| Module | Sub-module / Task                               | Status  |
| ------ | ----------------------------------------------- | ------- |
| Core   | `VectorStoreContract` + Pinecone implementation | 🟢 Done |
| Core   | DTOs (upsert, query, delete, index ops)         | 🟢 Done |
| Core   | PSR-18 HTTP transport + retries                 | 🟢 Done |
| Core   | Exponential backoff + 429 / `Retry-After`       | 🟢 Done |
| Core   | Observability hooks (request/error callbacks)   | 🟢 Done |
| Core   | `IndexAdminContract` (control plane)            | 🟢 Done |
| Core   | Configurable API version + index host           | 🟢 Done |
| Core   | PHPUnit coverage for client + retry             | 🟢 Done |

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

| Module   | Sub-module / Task                 | Status  |
| -------- | --------------------------------- | ------- |
| Eloquent | `HasEmbeddings` trait             | 🟢 Done |
| Eloquent | Auto-sync on create/update/delete | 🟢 Done |
| Eloquent | Sync modes (sync vs queued)       | 🟢 Done |
| Eloquent | Semantic search API               | 🟢 Done |
| Eloquent | Batch indexing API                | 🟢 Done |
| Eloquent | Soft delete handling              | 🟢 Done |
| Eloquent | Selective field indexing          | 🟢 Done |

---

## Phase 5 — DX & Hardening

Status: **complete** (see `doc/dx.md`).

| Module | Sub-module / Task         | Status  |
| ------ | ------------------------- | ------- |
| DX     | Query result caching      | 🟢 Done |
| DX     | Full PHPUnit coverage     | 🟢 Done |
| DX     | Root README with examples | 🟢 Done |
| DX     | Error classification      | 🟢 Done |
| DX     | Developer debug mode      | 🟢 Done |
| DX     | Config validation         | 🟢 Done |

---

## Phase 6 — Observability

Status: **complete** (see `doc/observability.md`).

| Module        | Sub-module / Task                           | Status  |
| ------------- | ------------------------------------------- | ------- |
| Observability | HTTP metrics contract + null driver         | 🟢 Done |
| Observability | Transport timing + correlation id           | 🟢 Done |
| Observability | Laravel `PineconeHttpRequestFinished` event | 🟢 Done |
| Observability | Config + docs                               | 🟢 Done |

---

## Phase 7 — Multi-Backend Support

Status: **complete** (see **[multi-backend.md](./multi-backend.md)**).

| Module        | Sub-module / Task                              | Status  |
| ------------- | ---------------------------------------------- | ------- |
| Multi-backend | Qdrant driver implementation                   | 🟢 Done |
| Multi-backend | Weaviate driver implementation                 | 🟢 Done |
| Multi-backend | Local driver (SQLite/file for testing)         | 🟢 Done |
| Multi-backend | pgvector (Postgres) driver                     | 🟢 Done |
| Multi-backend | Driver manager (`driver()` / config switching) | 🟢 Done |
| Multi-backend | Per-model driver support                       | 🟢 Done |
| Multi-backend | Driver capability detection                    | 🟢 Done |

---

## Phase 8 — RAG Pipeline

Status: **complete** (see **[rag.md](./rag.md)**).

| Module | Sub-module / Task                     | Status  |
| ------ | ------------------------------------- | ------- |
| RAG    | `RagPipeline` core class              | 🟢 Done |
| RAG    | Retrieval abstraction (topK, filters) | 🟢 Done |
| RAG    | `LLMDriver` interface                 | 🟢 Done |
| RAG    | OpenAI LLM driver                     | 🟢 Done |
| RAG    | Prompt builder (context injection)    | 🟢 Done |
| RAG    | `Vector::ask()` fluent API            | 🟢 Done |
| RAG    | Streaming responses support           | 🟢 Done |
| RAG    | Conversation memory (optional)        | 🟢 Done |

---

## Phase 9 — Data Ingestion

| Module    | Sub-module / Task               | Status     |
| --------- | ------------------------------- | ---------- |
| Ingestion | Text chunking strategies        | 🔴 Pending |
| Ingestion | File ingestion (PDF, DOCX, TXT) | 🔴 Pending |
| Ingestion | HTML / web ingestion            | 🔴 Pending |
| Ingestion | Metadata enrichment             | 🔴 Pending |
| Ingestion | Batch ingestion pipeline        | 🔴 Pending |
| Ingestion | Queue-based ingestion jobs      | 🔴 Pending |
| Ingestion | `Vector::ingest()` API          | 🔴 Pending |

---

## Phase 10 — Advanced Search

| Module | Sub-module / Task                | Status     |
| ------ | -------------------------------- | ---------- |
| Search | Hybrid search (vector + keyword) | 🔴 Pending |
| Search | Reranking support                | 🔴 Pending |
| Search | Advanced metadata filtering      | 🔴 Pending |
| Search | Faceted search                   | 🔴 Pending |
| Search | Pagination / cursor support      | 🔴 Pending |
| Search | Score normalization              | 🔴 Pending |

---

## Phase 11 — Developer Experience (DX 2.0)

| Module | Sub-module / Task                        | Status     |
| ------ | ---------------------------------------- | ---------- |
| DX     | Eloquent-first semantic API improvements | 🔴 Pending |
| DX     | `semanticWhere()` / `semanticOrderBy()`  | 🔴 Pending |
| DX     | Auto-vectorization via casting           | 🔴 Pending |
| DX     | PHP attribute-based config               | 🔴 Pending |
| DX     | CLI generators (`make:vector-model`)     | 🔴 Pending |
| DX     | Debug / playground tools                 | 🔴 Pending |
| DX     | Improved developer error messages        | 🔴 Pending |

---

## Phase 12 — Observability 2.0

| Module        | Sub-module / Task          | Status     |
| ------------- | -------------------------- | ---------- |
| Observability | End-to-end query tracing   | 🔴 Pending |
| Observability | Embedding latency tracking | 🔴 Pending |
| Observability | Token usage tracking       | 🔴 Pending |
| Observability | Cost estimation            | 🔴 Pending |
| Observability | Debug dashboard (optional) | 🔴 Pending |
| Observability | APM / logging integrations | 🔴 Pending |

---

## Future / Advanced

| Module        | Sub-module / Task            | Status     |
| ------------- | ---------------------------- | ---------- |
| Multi-backend | Qdrant / Weaviate / pgvector | 🟢 Done (Phase 7) |
| Abstraction   | Scout-style engine           | 🔴 Pending |
| Search        | Hybrid + reranking           | 🔴 Pending |
| AI            | Full RAG pipeline            | 🟢 Done (Phase 8 baseline) |
| Ingestion     | File + chunking pipeline     | 🔴 Pending |
| Observability | Cost + tracing + metrics     | 🔴 Pending |

---

## Versioning

Follow **SemVer**: breaking changes to contracts/config → major bump.
