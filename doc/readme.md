# Vectora — Laravel Pinecone Integration

**Vectora** is a **community-maintained PHP/Laravel SDK** for Pinecone’s **REST APIs**: vector upsert/query/delete, control-plane helpers, embeddings, and Eloquent-oriented workflows. Pinecone does not ship an **official PHP client**; this project exists so PHP teams have a credible, documented path to production—and so the work can be **shared with Pinecone** and the wider ecosystem as a **reference integration** (not an official Pinecone product unless Pinecone chooses to adopt or endorse it separately).

**New here?** Start with **[installation.md](./installation.md)** and the repository **[README.md](../README.md)** for Composer setup, `.env` keys, and verification.

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
│  VectorStoreContract → Pinecone, Qdrant, Weaviate, SQLite, …     │
│  HTTP · DTOs · retry · rate limits                               │
└─────────────────────────────────────────────────────────────────┘
```

### Why this shape

| Layer | Role |
|-------|------|
| **Core** | Pinecone REST client, retries, DTOs, and alternate **`VectorStoreContract`** drivers (memory, SQLite, Qdrant, Weaviate, pgvector). Testable without Laravel. |
| **Contracts** | `VectorStoreContract`, `EmbeddingDriver`, `EmbeddingProvider` — SOLID, mocking, custom drivers. |
| **Laravel** | Wiring only: provider, facade, config, logging callbacks. |
| **Eloquent** | Scout-like DX: sync lifecycle, metadata, batch/queue indexing. |
| **RAG (Phase 8)** | `RagPipeline`, `LLMDriver`, `Vector` facade / `Model::rag()` over `Embeddable` search. |
| **Ingestion (Phase 9)** | Chunking, file/HTML/URL extractors, `IngestionPipeline`, `Vector::ingest()`, `IngestUpsertJob`. |
| **Search (Phase 10)** | `Pinecone::advancedSearch()`, hybrid keyword boost, rerankers, facets, pagination, score normalization, optional sparse/hybrid query fields. |
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
  Contracts/      # VectorStoreContract, EmbeddingDriver, TextChunkingStrategy, …
  DTO/            # Request/response value objects, IngestedChunk
  Ingestion/      # Phase 9: chunking, extractors, pipeline, URL fetch
  Search/         # Phase 10: score normalization, rerankers, facets, filter helpers
  Laravel/        # ServiceProvider, Facade, IngestionBuilder, AdvancedSearchBuilder, RAG
  Eloquent/       # HasEmbeddings trait, searchable scope
  Jobs/           # UpsertModelEmbedding, IngestUpsertJob, …
  Commands/       # pinecone:sync, pinecone:flush
config/
tests/
doc/
```

---

## 3. Quick links

| Doc | Purpose |
|-----|---------|
| [installation.md](./installation.md) | Composer, publish config, `.env`, queues, troubleshooting |
| [readme.md](../README.md) | Repository overview and short install (GitHub landing) |
| [roadmap.md](./roadmap.md) | Phases, improvements, future vector DBs |
| [ci.md](./ci.md) | GitHub Actions (analysis, format, tests) |
| [core.md](./core.md) | Phase 1: PSR-18 core client usage |
| [laravel.md](./laravel.md) | Phase 2: service provider, jobs, commands |
| [embeddings.md](./embeddings.md) | Phase 3: EmbeddingDriver, OpenAI, cache |
| [eloquent.md](./eloquent.md) | Phase 4: HasEmbeddings, semantic search, batch |
| [dx.md](./dx.md) | Phase 5: query cache, debug logging, config validation, error classification |
| [observability.md](./observability.md) | Phase 6: HTTP metrics, correlation id, Laravel events |
| [multi-backend.md](./multi-backend.md) | Phase 7: alternate vector stores, `VectorStoreManager`, per-model driver |
| [rag.md](./rag.md) | Phase 8: RAG pipeline, LLM drivers, streaming, conversation memory |
| [ingestion.md](./ingestion.md) | Phase 9: chunking, file/web ingestion, `Vector::ingest()`, queue jobs |
