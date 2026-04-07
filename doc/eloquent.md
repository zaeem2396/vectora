# Eloquent integration (Phase 4 & Phase 11)

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

**Phase 11:** declare columns with **`#[EmbeddingColumns(columns: ['title', 'body'])]`** on the class and **omit** a manual **`vectorEmbeddingFields()`** override, or keep the explicit method — see **[dx.md](./dx.md)**.

- **`vectorEmbeddingFields()`** — attributes that feed `vectorEmbeddingText()` (newline-joined) and that trigger a re-upsert when changed.
- Override **`vectorEmbeddingMetadata()`** to add Pinecone metadata (merged with defaults `vectora_model`, `vectora_key`).
- Override **`vectorEmbeddingIndex()`** / **`vectorEmbeddingNamespace()`** for multi-index setups (static methods; `null` = config default). Optional **`#[VectorEmbeddingIndexName('posts')]`** covers the index name when you prefer attributes over overrides.
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

### Phase 11 — `semanticWhere()` / `semanticOrderBy()` (Eloquent builder)

`Model::query()` on an embeddable model returns **`SemanticEloquentBuilder`**:

```php
$published = Article::query()
    ->where('published', true)
    ->semanticWhere('async PHP patterns', topK: 25)
    ->get();
```

Invalid **`topK`** throws **`Vectora\Pinecone\Laravel\Exceptions\SemanticSearchInvalidArgumentException`**.

### Virtual embedding text via cast (Phase 11)

Use **`Vectora\Pinecone\Eloquent\Casts\ConcatEmbeddingTextCast`** so a single virtual attribute mirrors multiple columns; list that attribute in **`vectorEmbeddingFields()`**. See **[dx.md](./dx.md)**.

### Scaffold (Phase 11)

```bash
php artisan make:vector-model Article
```

---
