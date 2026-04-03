# Observability (Phase 6)

Phase 6 adds **HTTP metrics and tracing hooks** on top of the existing **`ObservabilityHooks`** (request logging). Metrics fire **once per logical Pinecone HTTP call** (after retries), with a **correlation id** you can propagate to logs or an APM.
