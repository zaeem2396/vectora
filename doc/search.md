# Phase 10 — Advanced search

Vectora adds a **search orchestration layer** on top of **`VectorStoreContract::query()`**: hybrid-style **keyword boosting**, pluggable **reranking**, **score normalization**, **faceted counts** from result sets, **offset pagination**, and optional **Pinecone-native hybrid / pagination** fields on **`QueryVectorsRequest`**.

---

## 1. Entry point: `Pinecone::advancedSearch()`

