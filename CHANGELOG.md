# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- **Phase 12 — Observability 2.0:** `VectorOperationTrace` for optional end-to-end trace ids.
- **Phase 12 — Observability 2.0:** `PineconeHttpRequestFinished::$traceId` when `VectorOperationTrace::begin()` is used.
- **Phase 12 — Observability 2.0:** `EmbeddingCallFinished` event (embedding latency, batch size, optional tokens and USD).
- **Phase 12 — Observability 2.0:** `LlmCallFinished` event for non-streaming chat (optional tokens and USD).
- **Phase 12 — Observability 2.0:** `ObservabilityCostEstimator` with configurable per-million-token USD tables.
- **Phase 12 — Observability 2.0:** `ObservedEmbeddingDriver` / `ObservedLlmDriver` wired from `EmbeddingManager` / `LLMManager` when `pinecone.observability_v2` is enabled.
- **Phase 12 — Observability 2.0:** `OpenAIEmbeddingDriver::lastUsage()` from embeddings API usage.
- **Phase 12 — Observability 2.0:** `OpenAILLMDriver::lastUsage()` from chat completions usage.
- **Phase 12 — Observability 2.0:** `CachingEmbeddingDriver::innerDriver()` for unwrapping cached OpenAI drivers.
- **Phase 12 — Observability 2.0:** `pinecone:observability` Artisan command for config summary.
- **Phase 12 — Observability 2.0:** `pinecone.observability_v2` config and env `VECTORA_OBSERVABILITY_V2` (+ embedding/LLM event toggles).
- **Phase 12 — Observability 2.0:** `PineconeConfigValidator` rules for observability_v2 cost tables.
- **Phase 12 — Observability 2.0:** `VectorOperationTrace` falls back safely when Laravel `Context` has no facade root (unit tests).
- **Phase 11 — DX 2.0:** `SemanticEloquentBuilder` with `semanticWhere()` / `semanticOrderBy()`; `#[EmbeddingColumns]` / `#[VectorEmbeddingIndexName]`; default `vectorEmbeddingFields()` resolution from attributes; `ConcatEmbeddingTextCast`; `SemanticSearchInvalidArgumentException`; `make:vector-model` and `pinecone:semantic-debug` Artisan commands; `pinecone.dx.semantic_debug` config; `reindexAllEmbeddings` scope closure accepts `SemanticEloquentBuilder`.
- **Phase 10 — Advanced search:** `Pinecone::advancedSearch()` / `AdvancedSearchBuilder`, `RerankerContract`, `KeywordBoostReranker`, `ScoreNormalizer`, `FacetAggregator`, `MetadataFilterComposer`, `OffsetPagination`; `pinecone.search` config; extended `QueryVectorsRequest` (sparse/hybrid/pagination token) and `QueryVectorsResult` (next pagination token); deterministic score sort in `PineconeVectorStore` query parsing.
- **Phase 9 — Data ingestion:** `TextChunkingStrategy`, file extractors (txt, HTML, DOCX, PDF), `IngestionPipeline`, `Vector::ingest()` fluent API, `IngestUpsertJob`, and `pinecone.ingestion` config defaults. Dependency: `smalot/pdfparser`.
