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
