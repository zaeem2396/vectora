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
