# 🚀 Vectora — Roadmap (Laravel + Pinecone)

## Status Legend
- 🟢 Done
- 🟡 In Progress
- 🔴 Pending

---

## Phase 0 — Foundation

| Module | Sub-module / Task | Status |
|--------|------------------|--------|
| Foundation | Architecture doc (`doc/readme.md`) | 🟢 Done |
| Foundation | Repository layout (`src/*`, `config/`, `tests/`, `doc/`) | 🟢 Done |
| Foundation | `composer.json`, PHPUnit, Testbench | 🔴 Pending |
| Foundation | CI setup (GitHub Actions) | 🔴 Pending |
| Foundation | Coding standards (Pint / PHP-CS-Fixer) | 🔴 Pending |

---

## Phase 1 — Core (Framework Agnostic)

| Module | Sub-module / Task | Status |
|--------|------------------|--------|
| Core | `VectorStoreContract` + Pinecone REST implementation | 🔴 Pending |
| Core | DTOs (upsert, query, delete, index ops) | 🔴 Pending |
| Core | PSR-18 client + optional Symfony HttpClient | 🔴 Pending |
| Core | Retries with exponential backoff | 🔴 Pending |
| Core | Rate-limit handling (429 + Retry-After) | 🔴 Pending |
| Core | Structured logging hooks (PSR-3 optional) | 🔴 Pending |
| Core | Timeout + circuit breaker support | 🔴 Pending |
| Core | Configurable index host resolver | 🔴 Pending |

---

## Phase 2 — Laravel Integration

| Module | Sub-module / Task | Status |
|--------|------------------|--------|
| Laravel | `config/pinecone.php` (publishable) | 🔴 Pending |
| Laravel | Service Provider + Facade | 🔴 Pending |
| Laravel | Queue jobs (single + batch upsert/delete) | 🔴 Pending |
| Laravel | Events (`VectorSynced`, `VectorFailed`) | 🔴 Pending |
| Laravel | Artisan commands (`pinecone:sync`, `pinecone:flush`) | 🔴 Pending |
| Laravel | Auto-discovery support | 🔴 Pending |
| Laravel | Env-based multi-index config | 🔴 Pending |

---

## Phase 3 — Embeddings

| Module | Sub-module / Task | Status |
|--------|------------------|--------|
| Embeddings | `EmbeddingDriver` interface | 🔴 Pending |
| Embeddings | OpenAI driver (reference implementation) | 🔴 Pending |
| Embeddings | Stub / array driver (testing) | 🔴 Pending |
| Embeddings | Custom driver docs (Ollama, local models) | 🔴 Pending |
| Embeddings | Batch embedding support | 🔴 Pending |
| Embeddings | Embedding caching (hash-based dedup) | 🔴 Pending |

---

## Phase 4 — Eloquent Integration

| Module | Sub-module / Task | Status |
|--------|------------------|--------|
| Eloquent | `HasEmbeddings` trait (fields, metadata, namespace) | 🔴 Pending |
| Eloquent | Auto-sync on create/update/delete | 🔴 Pending |
| Eloquent | Sync modes (sync vs queued) | 🔴 Pending |
| Eloquent | Semantic search API | 🔴 Pending |
| Eloquent | Batch indexing API | 🔴 Pending |
| Eloquent | Soft delete handling | 🔴 Pending |
| Eloquent | Selective field indexing (dirty check) | 🔴 Pending |

---

## Phase 5 — DX & Hardening

| Module | Sub-module / Task | Status |
|--------|------------------|--------|
| DX | Query result caching (TTL strategy) | 🔴 Pending |
| DX | Full PHPUnit coverage | 🔴 Pending |
| DX | Root README with examples | 🔴 Pending |
| DX | Error classification (retryable vs fatal) | 🔴 Pending |
| DX | Developer debug mode | 🔴 Pending |
| DX | Config validation (fail fast) | 🔴 Pending |

---

## Phase 6 — Observability

| Module | Sub-module / Task | Status |
|--------|------------------|--------|
| Observability | Structured logging hooks | 🔴 Pending |
| Observability | Metrics (latency, ops count) | 🔴 Pending |
| Observability | Tracing support (OpenTelemetry-style) | 🔴 Pending |

---

## Future / Bonus

| Module | Sub-module / Task | Status |
|--------|------------------|--------|
| Multi-backend | Second backend (Qdrant / Weaviate) | 🔴 Pending |
| Abstraction | Engine system (Scout-style) | 🔴 Pending |
| Search | Hybrid search (vector + keyword) | 🔴 Pending |
| AI | RAG helper utilities | 🔴 Pending |
| Queue | Horizon metrics support | 🔴 Pending |

---

## Versioning

Follow **SemVer**:
- Breaking changes to contracts/config → major version bump