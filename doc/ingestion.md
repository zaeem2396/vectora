# Phase 9 — Data ingestion

Vectora provides a **framework-agnostic ingestion core** (chunking, text extraction, pipeline) and a **Laravel fluent API** on the `Vector` facade: **`Vector::ingest()`**. Ingestion turns raw content into embedded vectors and upserts them through the same **`VectorStoreContract`** abstraction as Eloquent sync (Pinecone, memory, Qdrant, etc.).

---

## 1. Concepts

| Piece | Role |
|-------|------|
| **`TextChunkingStrategy`** | Splits normalized text into segments (`FixedSizeOverlappingChunker`, `ParagraphChunker`, or your own). |
| **`TextExtractor`** | Reads plain text from a **file path** by extension (txt, html, docx, pdf, …). |
| **`ExtractorRegistry`** | Chooses the first extractor that `supports($path)`. You may `register()` custom extractors first. |
| **`IngestionPipeline`** | Applies chunking + optional per-chunk enrichment → **`IngestedChunk`** DTOs. |
| **`IngestionBuilder`** | Laravel fluent API: `fromString` / `fromPath` / `fromUrl` → `chunks()` or `chunkUsing()` → `syncUpsert()` or `dispatchUpsert()`. |
| **`IngestUpsertJob`** | Queue job: `embedMany` + upsert (uses `pinecone.queue` like other vector jobs). |

**Embeddings** use the configured **`pinecone.embeddings`** driver (e.g. OpenAI in production, deterministic in tests). **Vector storage** uses **`pinecone.vector_store`** (same as `HasEmbeddings`).

---

## 2. Configuration

Published `config/pinecone.php` includes:

```php
'ingestion' => [
    'default_chunk_size' => (int) env('VECTORA_INGEST_CHUNK_SIZE', 512),
    'default_chunk_overlap' => (int) env('VECTORA_INGEST_CHUNK_OVERLAP', 64),
],
```

If you omit `->chunks()` on the builder, these defaults apply.

**Dependencies:**

- **PDF:** `smalot/pdfparser` (required by this package).
- **DOCX:** PHP **`ext-zip`** and **`simplexml`** (typically available).

---

## 3. Fluent API (`Vector::ingest()`)

```php
use Vectora\Pinecone\Laravel\Facades\Vector;

$n = Vector::ingest()
    ->fromPath(storage_path('app/docs/guide.txt'), ['collection' => 'help'])
    ->chunks(512, 64)
    ->enrich(function (\Vectora\Pinecone\DTO\IngestedChunk $chunk): \Vectora\Pinecone\DTO\IngestedChunk {
        return $chunk->withMetadata(['ingested_at' => now()->toIso8601String()]);
    })
    ->syncUpsert(vectorIdPrefix: 'doc-guide', index: null, namespace: 'kb');
```

**Parameters:**

- **`vectorIdPrefix`:** Vector IDs are `{prefix}-{chunkIndex}` (stable for a given run).
- **`index`:** Logical Pinecone index name when using the **pinecone** driver; ignored for memory/sqlite-only setups.
- **`namespace`:** Data-plane namespace for upsert.

**Web / HTML:**

```php
Vector::ingest()
    ->fromUrl('https://example.com/page.html')
    ->chunks(400, 40)
    ->syncUpsert('web-example', null, 'crawl');
```

HTML responses are passed through **`HtmlTextExtractor::htmlToText()`** when the body looks like markup (leading `<` after trim). Plain text responses are trimmed only.

**Async:**

```php
Vector::ingest()
    ->fromString($bigBlob)
    ->chunkUsing(new \Vectora\Pinecone\Ingestion\Chunking\ParagraphChunker(4, 3000))
    ->dispatchUpsert('batch-a', null, 'imports');
```

Requires a **queue worker**; job uses **`IngestUpsertJob`**.

---

## 4. Framework-only building blocks

Use without the facade in unit tests or custom services:

```php
use Vectora\Pinecone\Ingestion\Chunking\FixedSizeOverlappingChunker;
use Vectora\Pinecone\Ingestion\IngestionPipeline;

$pipeline = new IngestionPipeline;
$chunks = $pipeline->run(
    $plainText,
    new FixedSizeOverlappingChunker(256, 32),
    enrich: null,
    baseMetadata: ['source' => 'inline'],
);
```

---

## 5. Chunking strategies

| Class | Behavior |
|-------|----------|
| **`FixedSizeOverlappingChunker`** | Character windows of `$chunkSize` with `$overlap` (byte-oriented; prefer ASCII/Latin-heavy text or short units). |
| **`ParagraphChunker`** | Splits on blank lines, merges up to `maxParagraphsPerChunk` and soft `maxCharsSoft`. |

Implement **`TextChunkingStrategy`** for tokenizer-aware or UTF-8-safe strategies.

---

## 6. Extractors

| Class | Extensions |
|-------|------------|
| **`PlainTextExtractor`** | `txt`, `md`, `markdown`, `csv` |
| **`HtmlTextExtractor`** | `html`, `htm`, `xhtml` |
| **`DocxTextExtractor`** | `docx` |
| **`PdfTextExtractor`** | `pdf` (Smalot parser) |

Register a custom **`TextExtractor`** on **`ExtractorRegistry::register()`** for MIME-specific behavior.

---

## 7. HTTP URL fetching

**`GuzzleUrlReader`** (default for `fromUrl`) uses Guzzle with a 30s timeout and a Vectora user-agent. Inject your own for proxies or auth if you bind a replacement in the container.

---

## 8. Events

**`IngestUpsertJob`** dispatches **`VectorSynced`** / **`VectorFailed`** with operation `ingest_upsert`, same pattern as **`UpsertVectorsJob`**.

---

## 9. See also

- **[rag.md](./rag.md)** — Phase 8 RAG / `Vector::using(Model::class)`.
- **[multi-backend.md](./multi-backend.md)** — switching vector stores.
- **[embeddings.md](./embeddings.md)** — embedding drivers and dimensions (must match index).
