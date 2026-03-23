# Embeddings (Phase 3)

Text → vector pipeline via **`EmbeddingDriver`**. Core drivers live under `src/Embeddings/`; Laravel wires them from `config/pinecone.php` and can wrap results in **`CachingEmbeddingDriver`**.

---

## Configuration

| Env | Purpose |
|-----|---------|
| `PINECONE_EMBEDDING_DRIVER` | `deterministic` (default) or `openai` |
| `PINECONE_EMBEDDING_CACHE` | `true` to cache by SHA-256 of input |
| `PINECONE_EMBEDDING_CACHE_STORE` | Optional cache store name |
| `PINECONE_EMBEDDING_CACHE_PREFIX` | Key prefix (default `vectora.embeddings`) |
| `PINECONE_EMBEDDING_CACHE_TTL` | Seconds; omit for `rememberForever` |
| `PINECONE_EMBEDDING_DETERMINISTIC_DIMENSIONS` | Vector size for hash driver |
| `OPENAI_API_KEY` / `PINECONE_OPENAI_API_KEY` | OpenAI key |
| `OPENAI_EMBEDDING_MODEL` | e.g. `text-embedding-3-small` |
| `OPENAI_BASE_URL` | OpenAI-compatible base URL |

---

## Usage (Laravel)

```php
use Vectora\Pinecone\Contracts\EmbeddingDriver;
use Vectora\Pinecone\Laravel\Facades\Pinecone;

$v = Pinecone::embed('hello world');

$batch = Pinecone::embedMany(['a', 'b']);

$driver = Pinecone::embeddings('openai');
$driver->embed('only when using OpenAI explicitly');
```

Inject the default driver:

```php
public function __construct(private EmbeddingDriver $embeddings) {}
```

---

## Drivers

### `deterministic`

Pseudo-embeddings from `sha256(text)` — **no network**, stable for tests and local dev. Not compatible with real Pinecone index dimensions unless you match `dimensions` to your index.

### `openai`

Calls `POST /v1/embeddings` with batched `input` when `embedMany()` is used (`batch_size` from config). Requires `api_key`.

---

## Custom drivers

Implement `Vectora\Pinecone\Contracts\EmbeddingDriver`:

```php
final class MyDriver extends AbstractEmbeddingDriver
{
    public function embed(string $text): array
    {
        // return list<float>
    }
    // Optional: override embedMany() for batch APIs
}
```

Register in a service provider (after the package boots):

```php
$this->app->extend(EmbeddingDriverFactory::class, function ($factory, $app) {
    // or bind a custom EmbeddingManager / replace `pinecone.embeddings` config
});
```

For a **named** driver you would extend `EmbeddingDriverFactory::make()` via a PR or local subclass — the factory currently resolves `deterministic` and `openai` only.

---

## Caching

When `embeddings.cache.enabled` is true, **`EmbeddingManager`** wraps the resolved driver with **`CachingEmbeddingDriver`**: one cache entry per distinct input string. `embedMany()` fetches missing keys in **one** inner batch when possible.

---

## See also

- [roadmap.md](./roadmap.md) — Phase 3 checklist  
- [laravel.md](./laravel.md) — Service provider & facade  
