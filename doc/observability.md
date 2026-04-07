# Observability (Phase 6 & Phase 12)

Phase 6 adds **HTTP metrics and tracing hooks** on top of the existing **`ObservabilityHooks`** (request logging). Metrics fire **once per logical Pinecone HTTP call** (after retries), with a **correlation id** you can propagate to logs or an APM.

---

## Laravel: events

Set **`PINECONE_METRICS=true`** (or `pinecone.metrics.enabled` in config). Each completed transport call dispatches:

**`Vectora\Pinecone\Laravel\Events\PineconeHttpRequestFinished`**

| Property | Meaning |
|----------|---------|
| `correlationId` | 16-character hex id for this logical request |
| `method` | HTTP method (`GET`, `POST`, `DELETE`, …) |
| `path` | URI path (e.g. `/vectors/upsert`) |
| `durationSeconds` | Wall time including retries |
| `httpStatus` | Final HTTP status when a response was received (2xx–5xx) |
| `failureClass` | Exception class when the client gave up without a final HTTP status |

Either `httpStatus` **or** `failureClass` is set, not both.

Register a listener in your app (e.g. `Event::listen`) to push histograms, structured logs, or OpenTelemetry spans.

---

## Framework-agnostic core

**`Vectora\Pinecone\Contracts\PineconeMetrics`** is the interface implemented by:

- **`NullPineconeMetrics`** — default when metrics are off
- **`EventDispatchingPineconeMetrics`** — Laravel implementation that dispatches the event above

Pass a custom **`PineconeMetrics`** implementation as the last constructor argument to **`PineconeHttpTransport`** if you use the core client outside Laravel.

---

## Relation to Phase 5

- **`ObservabilityHooks`** — fine-grained before/after/error callbacks (logging, debug bodies).
- **`PineconeMetrics`** — one summary record per operation with duration and correlation id.

You can enable both independently.

---

## Related

- **[core.md](./core.md)** — building `PineconeHttpTransport`
- **[dx.md](./dx.md)** — debug logging and query cache
- **[laravel.md](./laravel.md)** — service provider and facade
- **[ingestion.md](./ingestion.md)** — Phase 9 jobs dispatch **`VectorSynced`** / **`VectorFailed`** for `ingest_upsert` (same as other vector jobs)
- **[search.md](./search.md)** — Phase 10 advanced search reuses the same vector **`query()`** path (cache/metrics apply as today)
- **[dx.md](./dx.md)** — Phase 11 semantic builder and **`pinecone:semantic-debug`** hit the same query path when enabled

---

## Phase 12 — Observability 2.0

Phase 12 layers **trace correlation**, **embedding and LLM call telemetry**, and **rough USD estimates** on top of Phase 6 HTTP metrics. Everything is **opt-in** via **`pinecone.observability_v2`** (env: **`VECTORA_OBSERVABILITY_V2`**).

### Trace id

**`Vectora\Pinecone\Laravel\Observability\VectorOperationTrace`**

| Method | Purpose |
|--------|---------|
| `begin(): string` | Create and store a 16-byte hex trace id (Laravel **`Context`** when the app is bootstrapped; otherwise a per-process fallback safe for unit tests). |
| `current(): ?string` | Active id from `begin()`, or `null`. |
| `clear(): void` | Remove the trace (e.g. end of job). |

Call **`VectorOperationTrace::begin()`** at the start of an HTTP request, queue job, or CLI entrypoint you want to correlate. When **`PINECONE_METRICS`** is enabled, **`PineconeHttpRequestFinished`** includes **`traceId`** so logs or APM can join Pinecone HTTP spans with embedding/LLM spans.

### Laravel events

**`EmbeddingCallFinished`** — after each **`embed()`** / **`embedMany()`** when **`observability_v2.enabled`** and **`embedding_events`** are true.

| Property | Meaning |
|----------|---------|
| `traceId` | From **`VectorOperationTrace::current()`** |
| `driverName` | Resolved config key (`openai`, `deterministic`, …) |
| `operation` | `embed` or `embed_many` |
| `durationSeconds` | Wall time for the call |
| `inputCharacters` | Sum of `strlen` for inputs (approximate payload size) |
| `batchSize` | Number of texts in the call |
| `totalTokens` | From OpenAI **`usage.total_tokens`** when the inner driver is **`OpenAIEmbeddingDriver`** (including behind **`CachingEmbeddingDriver`**) |
| `estimatedCostUsd` | From **`ObservabilityCostEstimator`** using **`pinecone.observability_v2.costs.embedding_usd_per_1m_tokens`** |

**`LlmCallFinished`** — after each non-streaming **`LLMDriver::chat()`** when **`llm_events`** is true. **`OpenAILLMDriver`** supplies **`prompt_tokens`**, **`completion_tokens`**, **`total_tokens`** from the last response; streaming **`streamChat()`** is not instrumented for tokens in this phase. **`estimatedCostUsd`** uses **`costs.chat_usd_per_1m_tokens`** (single blended rate applied to prompt + completion tokens).

### Configuration

See **`config/pinecone.php`** → **`observability_v2`**: boolean flags, and **`costs.embedding_usd_per_1m_tokens`** / **`costs.chat_usd_per_1m_tokens`** maps keyed by model id (same strings as **`OPENAI_EMBEDDING_MODEL`**, **`OPENAI_CHAT_MODEL`**, etc.). Override env vars such as **`VECTORA_COST_EMBEDDING_3_SMALL_PER_1M`** for quick tweaks without editing arrays.

**`php artisan pinecone:observability`** prints whether v2 is enabled, whether embedding/LLM events are on, and how many cost rows are configured (no secrets).

### Integrations

Register **`Event::listen`** handlers for **`EmbeddingCallFinished`**, **`LlmCallFinished`**, and **`PineconeHttpRequestFinished`** to forward to OpenTelemetry, Datadog, structured logs, or internal dashboards. There is no bundled UI; Phase 12 is **hooks and data**, not a hosted dashboard.
