# Observability (Phase 6)

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
