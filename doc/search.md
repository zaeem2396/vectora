# Phase 10 — Advanced search

Vectora adds a **search orchestration layer** on top of **`VectorStoreContract::query()`**: hybrid-style **keyword boosting**, pluggable **reranking**, **score normalization**, **faceted counts** from result sets, **offset pagination**, and optional **Pinecone-native hybrid / pagination** fields on **`QueryVectorsRequest`**.

---

## 1. Entry point: `Pinecone::advancedSearch()`

```php
use Vectora\Pinecone\Laravel\Facades\Pinecone;

$result = Pinecone::advancedSearch()
    ->queryText('How do we handle caching?')
    ->fetchTopK(40)
    ->namespace('docs')
    ->filter(['visibility' => ['$eq' => 'public']])
    ->withKeywordBoost(metadataKey: 'text', extraTokens: ['redis'])
    ->normalizeMinMax()
    ->withFacets(['category', 'year'])
    ->paginate(page: 1, perPage: 10)
    ->execute();

// $result->matches — list<QueryVectorMatch>
// $result->facets — e.g. ['category' => ['guides' => 5, 'api' => 3]]
// $result->totalAvailable — rows before current page slice
```

**`Pinecone::advancedSearch(?string $index, ?string $embeddingDriver)`** resolves the vector store via **`VectorStoreManager`** (same as Eloquent / multi-backend). Embeddings use **`pinecone.embeddings`**.

Defaults for fetch size and keyword boost come from **`config('pinecone.search')`** (see published `config/pinecone.php`).

---

## 2. Features vs backends

| Capability | Behaviour |
|------------|-----------|
| **Vector retrieval** | Standard dense `query()` with embedding of `queryText()`. |
| **Keyword “hybrid”** | **Client-side** rerank: boosts score when a metadata field (default `text`) contains query tokens (see **`KeywordBoostReranker`**). Not Pinecone sparse vectors unless you also pass **`sparseVector`** (below). |
| **Reranking** | Implement **`RerankerContract`** and **`->rerankWith($r)`** for custom order. |
| **Score normalization** | **`normalizeMinMax()`** or **`normalizeSoftmax()`** on the match list after rerank. |
| **Facets** | **`FacetAggregator`** counts distinct metadata values **on the current result set** (approximate; not a full index facet engine). |
| **Pagination** | **`paginate($page, $perPage)`** slices matches after fetch/rerank/normalize. Use a **large enough `fetchTopK()`** so pagination has data. |
| **Pinecone hybrid API** | Optional **`QueryVectorsRequest`** fields: **`sparseVector`**, **`hybridAlpha`**, **`paginationToken`** — forwarded in JSON for indexes that support them. |
| **Sorted hits** | **`PineconeVectorStore`** sorts matches by **score descending** when parsing responses (deterministic ordering). |

---

## 3. Metadata filters

Use Pinecone’s JSON filter DSL, or helpers in **`MetadataFilterComposer`**:

```php
use Vectora\Pinecone\Search\MetadataFilterComposer;

$filter = MetadataFilterComposer::allOf([
    'tenant_id' => ['$eq' => 'acme'],
    'year' => ['$gte' => 2020],
]);
```

Compose with **`$and` / `$or`**, **`$in`**, **`$exists`** as in **[Pinecone filter reference](https://docs.pinecone.io/guides/data/filter-with-metadata)**.

---

## 4. Configuration

```php
'search' => [
    'default_fetch_top_k' => (int) env('VECTORA_SEARCH_FETCH_TOP_K', 50),
    'keyword_boost_per_token' => (float) env('VECTORA_SEARCH_KEYWORD_BOOST', 0.05),
],
```

---

## 5. Framework-only utilities

| Class | Role |
|-------|------|
| **`ScoreNormalizer`** | `minMax()`, `softmax()` over **`QueryVectorMatch`** lists. |
| **`KeywordBoostReranker`** | Implements **`RerankerContract`**. |
| **`FacetAggregator`** | `aggregate($matches, $keys)` |
| **`OffsetPagination`** | `slice($matches, $offset, $limit)` |

---

## 6. See also

- **[multi-backend.md](./multi-backend.md)** — which store backs `advancedSearch()`.
- **[embeddings.md](./embeddings.md)** — embedding dimensions must match the index.
- **[rag.md](./rag.md)** — RAG uses retrieval + LLM; advanced search focuses on retrieval quality.
- **[eloquent.md](./eloquent.md)** — Phase 11 `semanticWhere()` / `semanticOrderBy()` on the Eloquent builder.
- **[roadmap.md](./roadmap.md)** — future phases beyond 12.
- **[observability.md](./observability.md)** — Phase 12 trace and metrics for the same `query()` path.
