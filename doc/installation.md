# Installation

This guide covers installing **Vectora** (`vectora/laravel-pinecone`) in a Laravel application and the minimum configuration to call Pinecone.

---

## 1. Prerequisites

| Requirement | Notes |
|-------------|--------|
| PHP | **8.2+** with `ext-json` |
| Laravel | **11.x** or **12.x** |
| Pinecone | API key and index **host** (from the Pinecone console) |
| Queue (optional) | Redis, database, or other Laravel queue driver if you use **queued** jobs or `PINECONE_ELOQUENT_SYNC=queued` |

---

## 2. Install via Composer

### From Packagist (when published)

```bash
composer require vectora/laravel-pinecone
```

### From GitHub (VCS) before Packagist

Add a VCS repository and require the branch you track (often `main`):

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/cod3xa/vectora.git"
        }
    ],
    "require": {
        "vectora/laravel-pinecone": "dev-main"
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

Then:

```bash
composer update vectora/laravel-pinecone
```

---

## 3. Laravel integration

1. **Auto-discovery** — The package registers `PineconeServiceProvider` and the **`Pinecone`** facade via `composer.json` `extra.laravel`. No manual `config/app.php` entry is required unless you disable package discovery.

2. **Publish config** (recommended):

   ```bash
   php artisan vendor:publish --tag=pinecone-config
   ```

   This copies `config/pinecone.php` into your app so you can rely on `.env` without editing vendor files.

---

## 4. Environment variables

At minimum, set:

| Variable | Purpose |
|----------|---------|
| `PINECONE_API_KEY` | Pinecone API key |
| `PINECONE_HOST` | Data-plane host for your index (or configure `indexes` in config) |

Common optional keys (see published `config/pinecone.php` for the full list):

| Variable | Purpose |
|----------|---------|
| `PINECONE_INDEX` | Default logical index name when using named `indexes` |
| `PINECONE_NAMESPACE` | Default namespace for upsert/query/delete |
| `PINECONE_API_VERSION` | `X-Pinecone-Api-Version` header (default aligned with package) |
| `PINECONE_ELOQUENT_SYNC` | `sync` or `queued` for `HasEmbeddings` models |
| `PINECONE_EMBEDDING_DRIVER` | `deterministic` (dev) or `openai` |
| `OPENAI_API_KEY` | For OpenAI embeddings when using the `openai` driver |
