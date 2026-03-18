# Roadmap — Vectora (Laravel + Pinecone)

## Phase 0 — Foundation ✅ (current)

- [x] Architecture doc (`doc/readme.md`)
- [x] Repository layout (`src/*`, `config/`, `tests/`, `doc/`)
- [ ] `composer.json`, PHPUnit, Testbench

## Phase 1 — Core (framework-agnostic)

- [ ] `VectorStoreContract` + Pinecone REST implementation
- [ ] DTOs: upsert, query, delete, index describe/create/delete
- [ ] PSR-18 client + optional Symfony HttpClient
- [ ] Retries with exponential backoff + configurable max attempts
- [ ] Rate-limit handling (429 + `Retry-After`)
- [ ] Structured logging hooks (callbacks / PSR-3 optional)

## Phase 2 — Laravel integration

- [ ] `config/pinecone.php` (publishable)
- [ ] `PineconeServiceProvider`, facade
- [ ] Queue jobs: single + batch upsert, delete by model
- [ ] Events: `VectorSynced`, `VectorFailed`
- [ ] Artisan: `pinecone:sync`, `pinecone:flush`

## Phase 3 — Embeddings

- [ ] `EmbeddingDriver` interface
- [ ] OpenAI driver (reference implementation)
- [ ] Stub / array driver for tests
- [ ] Documentation: registering custom drivers (Ollama, local, etc.)

## Phase 4 — Eloquent

- [ ] `HasEmbeddings` trait: fields, metadata, namespace
- [ ] Auto-sync on create/update/delete (sync + queued modes)
- [ ] `Document::semanticSearch($q)->get()` API
- [ ] Batch indexing API

## Phase 5 — DX & hardening

- [ ] Optional query-result caching (TTL, cache key strategy)
- [ ] Full PHPUnit coverage: client (mocked HTTP), sync, search
- [ ] Root README with examples (RAG, filters, namespaces)

## Future / bonus

- [ ] Second backend implementing `VectorStoreContract` (e.g. Qdrant, Weaviate)
- [ ] Laravel Scout-style “engine” abstraction if overlap grows
- [ ] Horizon-friendly job batching metrics

---

## Versioning

Follow **SemVer**. Breaking changes to contracts or config keys → major bump.
