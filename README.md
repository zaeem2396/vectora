# Vectora

**Community-built PHP/Laravel SDK for [Pinecone](https://www.pinecone.io/)** — embeddings, vector upsert/query/delete, Eloquent sync, and queue-friendly jobs over Pinecone’s **REST API**. Pinecone does not publish an official PHP client; Vectora is maintained independently and is intended to be **useful to PHP developers** and **straightforward to share with Pinecone** as a reference Laravel integration (not an official Pinecone product).

**Requirements:** PHP **8.2+**, Laravel **11.x or 12.x**, `ext-json`.

## Install

```bash
composer require vectora/laravel-pinecone
```

If the package is not yet on [Packagist](https://packagist.org/), require it from GitHub:

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

Laravel **auto-discovers** `PineconeServiceProvider` and registers the **`Pinecone`** facade.

Publish configuration:

```bash
php artisan vendor:publish --tag=pinecone-config
```

Set at least **`PINECONE_API_KEY`** and your index **host** (see `config/pinecone.php` and env keys `PINECONE_HOST` / `indexes`). For **OpenAI** embeddings, set **`OPENAI_API_KEY`** (or `PINECONE_OPENAI_API_KEY`).

Run a **queue worker** if you use queued upserts/deletes or `HasEmbeddings` with `PINECONE_ELOQUENT_SYNC=queued`:

```bash
php artisan queue:work
```

## Documentation

| Doc | Content |
|-----|---------|
| **[doc/installation.md](doc/installation.md)** | Install, env reference, verification, troubleshooting |
| **[doc/readme.md](doc/readme.md)** | Architecture, design, doc index |
| **[doc/laravel.md](doc/laravel.md)** | Service provider, jobs, commands, multi-index |
| **[doc/embeddings.md](doc/embeddings.md)** | Embedding drivers, OpenAI, caching |
| **[doc/eloquent.md](doc/eloquent.md)** | `HasEmbeddings`, semantic search, batch reindex |
| **[doc/core.md](doc/core.md)** | Framework-agnostic HTTP client usage |
| **[doc/dx.md](doc/dx.md)** | Query cache, debug HTTP logging, config validation, `ApiException` categories |
| **[doc/roadmap.md](doc/roadmap.md)** | Phases and future work |
