# Eloquent integration (Phase 4)

Models implement **`Embeddable`** and use **`HasEmbeddings`** to keep Pinecone vectors aligned with rows: create/update/delete (including soft delete) and optional **queued** sync.

---

## Setup

```php
use Illuminate\Database\Eloquent\SoftDeletes;
use Vectora\Pinecone\Eloquent\AbstractEmbeddableModel;

final class Article extends AbstractEmbeddableModel
{
    use SoftDeletes; // optional

    public static function vectorEmbeddingFields(): array
    {
        return ['title', 'body'];
    }
}
```

Alternatively, `implements Embeddable` and `use HasEmbeddings` on any `Model` subclass.

- **`vectorEmbeddingFields()`** — attributes that feed `vectorEmbeddingText()` (newline-joined) and that trigger a re-upsert when changed.
- Override **`vectorEmbeddingMetadata()`** to add Pinecone metadata (merged with defaults `vectora_model`, `vectora_key`).
- Override **`vectorEmbeddingIndex()`** / **`vectorEmbeddingNamespace()`** for multi-index setups (static methods; `null` = config default).
- Override **`vectorEmbeddingStoreDriver()`** to target a non-default **`pinecone.vector_store`** driver (`memory`, `sqlite`, etc.); `null` uses the global default (see **[multi-backend.md](./multi-backend.md)**).
- **`Article::rag()`** — fluent RAG entry (retrieve + LLM); same options as **`Vector::using(Article::class)`** (see **[rag.md](./rag.md)**).

---

## Sync mode

| Source | Behaviour |
|--------|-----------|
| `PINECONE_ELOQUENT_SYNC=sync` (or `config('pinecone.eloquent.default_sync')`) | Upsert/delete inline during model events. |
| `queued` | Dispatches **`SyncModelEmbeddingJob`** (upsert) and **`DeleteVectorsJob`** (delete). |

Per model:

```php
public static function vectorEmbeddingSyncMode(): string
{
    return 'sync';
}
```

---

## Lifecycle

| Event | Action |
|-------|--------|
| `created` | Upsert vector (unless `shouldSyncVectorEmbedding()` is false). |
| `updated` | Upsert only if an embedding field changed. |
| `deleted` | Delete vector id (soft or hard delete). |
| `restored` | Re-upsert (soft deletes only). |

Empty embedding text runs a **delete** instead of upserting.

---

## Semantic search

```php
$result = Article::semanticSearch('why is the sky blue', topK: 10);
$models = Article::semanticSearchModels('why is the sky blue');
```

Default metadata filter: `{ "vectora_model": Article::class }`. Return **`null`** from **`semanticSearchMetadataFilter()`** to search the whole namespace. Combine with an extra filter via the `$additionalFilter` argument (`$and` is built for you).

---

## Batch indexing

```php
Article::upsertVectorEmbeddingsForModels($collection);
Article::reindexAllEmbeddings(chunkSize: 100, scope: fn ($q) => $q->where('published', true));
```

In **`sync`** mode, batch upserts use **`embedMany()`** + a single Pinecone upsert per chunk. In **`queued`** mode, each model dispatches its own **`SyncModelEmbeddingJob`**.

---

## See also

- [roadmap.md](./roadmap.md) — Phase 4 checklist  
- [embeddings.md](./embeddings.md) — embedding drivers  
- [laravel.md](./laravel.md) — jobs, config, facade  
- [ingestion.md](./ingestion.md) — Phase 9: bulk ingest paths that are not tied to a single Eloquent row  
- [search.md](./search.md) — Phase 10: `advancedSearch()` as an alternative entry point to raw `semanticSearch()`  
