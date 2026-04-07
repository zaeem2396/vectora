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

