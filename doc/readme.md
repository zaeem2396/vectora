# Vectora — Laravel Pinecone Integration

Production-grade, Laravel-first vector search built on Pinecone’s REST API (no official PHP SDK).

---

## 1. High-level architecture

The package is split so **vector storage is abstract** and **Laravel is optional** at the edges.

```
┌─────────────────────────────────────────────────────────────────┐
│                     Laravel application                          │
│  Eloquent (HasEmbeddings) · Facade · Commands · Jobs · Events   │
└────────────────────────────┬────────────────────────────────────┘
                             │ binds interfaces, config, queue
┌────────────────────────────▼────────────────────────────────────┐
│              Laravel integration layer                           │
│  Service provider · config/pinecone.php · observability hooks    │
└────────────────────────────┬────────────────────────────────────┘
                             │ uses
┌────────────────────────────▼────────────────────────────────────┐
│  Embedding pipeline (EmbeddingDriver interface)                  │
│  OpenAI · Ollama · custom drivers                                │
└────────────────────────────┬────────────────────────────────────┘
                             │ vectors
┌────────────────────────────▼────────────────────────────────────┐
│  Core layer (framework-agnostic)                                 │
│  VectorStoreContract → Pinecone implementation                   │
│  HTTP · DTOs · retry · rate limits                               │
└─────────────────────────────────────────────────────────────────┘
```

### Why this shape

| Layer | Role |
|-------|------|
| **Core** | Single place for Pinecone REST calls, auth, retries, DTOs. Testable without Laravel. Swappable backend later (e.g. another `VectorStoreContract`). |
| **Contracts** | `VectorStoreContract`, `EmbeddingDriver`, `EmbeddingProvider` — SOLID, mocking, custom drivers. |
| **Laravel** | Wiring only: provider, facade, config, logging callbacks. |
| **Eloquent** | Scout-like DX: sync lifecycle, metadata, batch/queue indexing. |
| **Jobs / Commands** | Async and operational surfaces (`pinecone:sync`, `pinecone:flush`). |

### Data flow (RAG / semantic search)

1. **Index:** Model text → `EmbeddingDriver::embed()` → vector + metadata → `VectorStoreContract::upsert()`.
2. **Search:** Query string → embed → `query()` with `topK`, namespace, metadata filter → map IDs back to Eloquent (optional).

### Design principles

- **No Pinecone types in Eloquent** — models depend on contracts, not HTTP details.
- **PSR-18** for HTTP — Laravel supplies Guzzle/Symfony via `Http::` or discovery.
- **Failures as events** — `VectorFailed` for DLQ/monitoring; `VectorSynced` for audits.

See **[roadmap.md](./roadmap.md)** for phased delivery and future work.

---

## 2. Folder structure (target)

```
src/
  Core/           # Client, Pinecone adapter, retry middleware
  Contracts/      # VectorStoreContract, EmbeddingDriver, …
  DTO/            # Request/response value objects
  Laravel/        # ServiceProvider, Facade
  Eloquent/       # HasEmbeddings trait, searchable scope
  Jobs/           # UpsertModelEmbedding, BatchSync, …
  Commands/       # pinecone:sync, pinecone:flush
config/
tests/
doc/
```

---

## 3. Quick links

| Doc | Purpose |
|-----|---------|
| [roadmap.md](./roadmap.md) | Phases, improvements, future vector DBs |
| [ci.md](./ci.md) | GitHub Actions (analysis, format, tests) |
| [core.md](./core.md) | Phase 1: PSR-18 core client usage |
| [laravel.md](./laravel.md) | Phase 2: service provider, jobs, commands |

Root **`README.md`** (repository root) will hold install, badges, and copy-paste examples once the API stabilizes.
