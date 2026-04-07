# Phase 8 — RAG pipeline

**RAG** (retrieval-augmented generation) combines **semantic search** over your `Embeddable` models with an **LLM** that answers using retrieved context.

## Configuration

`config/pinecone.php` → **`llm`**:

| Key | Purpose |
|-----|---------|
| `default` | Driver name: `stub` (offline / tests) or `openai`. Env: **`VECTORA_LLM_DRIVER`**. |
| `drivers.stub.prefix` | Prepended to the stub “reply” (echo of the user message). Env: **`VECTORA_LLM_STUB_PREFIX`**. |
| `drivers.openai` | Same API key pattern as embeddings: **`OPENAI_API_KEY`**, model **`OPENAI_CHAT_MODEL`** (default `gpt-4o-mini`), optional **`OPENAI_CHAT_MAX_TOKENS`**, **`OPENAI_CHAT_TEMPERATURE`**. |

Config validation rejects unknown `llm.default` values and non-array `llm` / `llm.drivers` sections.

## Entry points

### `Vector` facade

```php
use Vectora\Pinecone\Laravel\Facades\Vector;
use App\Models\Article;

$answer = Vector::using(Article::class)
    ->topK(8)
    ->filter(['lang' => ['$eq' => 'en']]) // optional Pinecone metadata filter
    ->systemPrompt('You are a concise assistant.')
    ->ask('What did we write about caching?');

echo $answer->text;
// $answer->sources is a list of RagSourceChunk (id, text, score, metadata)
```

### Eloquent helper

Models using **`HasEmbeddings`** also expose:

```php
$answer = Article::rag()->topK(5)->ask('…');
```

### Streaming

Retrieval runs first (blocking); then the LLM output is streamed:

```php
foreach (Article::rag()->streamAsk('Explain chunking') as $delta) {
    echo $delta;
}
```

### Conversation memory

Optional **`InMemoryConversationMemory`** (or your own **`ConversationMemory`**) keeps prior user/assistant turns for multi-step dialogs:

```php
use Vectora\Pinecone\Rag\InMemoryConversationMemory;

$memory = new InMemoryConversationMemory;

Vector::using(Article::class)
    ->memory($memory)
    ->ask('What is this doc about?');

Vector::using(Article::class)
    ->memory($memory)
    ->ask('List two keywords from it.');
```

## Architecture

| Piece | Role |
|-------|------|
| **`RagRetrieverContract`** | `retrieve(query, topK, filter)` → **`RagSourceChunk[]`**. |
| **`EmbeddableRagRetriever`** | Uses **`Embeddable::semanticSearch()`**, hydrates rows for embedding text. |
| **`RagPromptBuilder`** | Injects numbered context into a system prompt + chat messages. |
| **`LLMDriver`** | `chat()` and **`streamChat()`** (OpenAI Chat Completions + SSE deltas). |
| **`RagPipeline`** | Orchestrates retrieve → prompt → LLM; optional memory. |
| **`LLMManager` / `LLMDriverFactory`** | Same pattern as embedding drivers; **`LLMDriver`** is container-resolvable. |

## Custom retrievers and LLMs

Implement **`RagRetrieverContract`** or **`LLMDriver`**, bind your implementation in a service provider, or resolve a named driver via **`LLMManager::driver('your_driver')`** after extending **`LLMDriverFactory`**.

## Requirements

- **Embeddings** must match your index (same model as `semanticSearch`).
- **Vector store** must support query + metadata filters used by your model (see **[multi-backend.md](./multi-backend.md)** capability flags).
- **OpenAI LLM** driver requires a non-empty API key when that driver is instantiated.

---

**Phase 9 (ingestion):** load documents with **`Vector::ingest()`** before or alongside RAG — see **[ingestion.md](./ingestion.md)**.

**Phase 10 (retrieval quality):** tune hybrid keyword boost, reranking, and facets with **`Pinecone::advancedSearch()`** — see **[search.md](./search.md)**.

**Phase 11 (Eloquent DX):** compose SQL filters with **`semanticWhere()`** / **`semanticOrderBy()`** — see **[eloquent.md](./eloquent.md)** and **[dx.md](./dx.md)**.

See **[roadmap.md](./roadmap.md)** for Phase 12+ (Observability 2.0).
