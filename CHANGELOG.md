# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- **Phase 11 — DX 2.0:** `SemanticEloquentBuilder` with `semanticWhere()` / `semanticOrderBy()`; `#[EmbeddingColumns]` / `#[VectorEmbeddingIndexName]`; default `vectorEmbeddingFields()` resolution from attributes; `ConcatEmbeddingTextCast`; `SemanticSearchInvalidArgumentException`; `make:vector-model` and `pinecone:semantic-debug` Artisan commands; `pinecone.dx.semantic_debug` config; `reindexAllEmbeddings` scope closure accepts `SemanticEloquentBuilder`.
- **Phase 10 — Advanced search:** `Pinecone::advancedSearch()` / `AdvancedSearchBuilder`, `RerankerContract`, `KeywordBoostReranker`, `ScoreNormalizer`, `FacetAggregator`, `MetadataFilterComposer`, `OffsetPagination`; `pinecone.search` config; extended `QueryVectorsRequest` (sparse/hybrid/pagination token) and `QueryVectorsResult` (next pagination token); deterministic score sort in `PineconeVectorStore` query parsing.
- **Phase 9 — Data ingestion:** `TextChunkingStrategy`, file extractors (txt, HTML, DOCX, PDF), `IngestionPipeline`, `Vector::ingest()` fluent API, `IngestUpsertJob`, and `pinecone.ingestion` config defaults. Dependency: `smalot/pdfparser`.
