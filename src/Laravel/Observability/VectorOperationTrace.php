<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel\Observability;

use Illuminate\Support\Facades\Context;
use Vectora\Pinecone\Laravel\Events\PineconeHttpRequestFinished;

/**
 * Phase 12: optional end-to-end trace id for correlating Pinecone HTTP metrics with embedding/LLM calls.
 *
 * Call {@see begin()} at the start of a request or job; {@see current()} is forwarded on
 * {@see PineconeHttpRequestFinished} and embedding/LLM events when enabled.
 */
final class VectorOperationTrace
{
    private const CONTEXT_KEY = 'vectora.trace_id';

    /** @var array<string, string> */
    private static array $fallback = [];

    /**
     * Start a new trace id for the current PHP request (or CLI process). Returns the id to log or propagate.
     */
    public static function begin(): string
    {
        $id = bin2hex(random_bytes(8));
        if (class_exists(Context::class)
            && Context::getFacadeRoot() !== null) {
            Context::add(self::CONTEXT_KEY, $id);
        } else {
            self::$fallback[self::fallbackKey()] = $id;
        }

        return $id;
    }

    /**
     * Active trace id from {@see begin()}, or null if none was started.
     */
    public static function current(): ?string
    {
        if (class_exists(Context::class)
            && Context::getFacadeRoot() !== null) {
            $v = Context::get(self::CONTEXT_KEY);

            return is_string($v) && $v !== '' ? $v : null;
        }

        return self::$fallback[self::fallbackKey()] ?? null;
    }

    public static function clear(): void
    {
        if (class_exists(Context::class)
            && Context::getFacadeRoot() !== null) {
            Context::forget(self::CONTEXT_KEY);
        }
        unset(self::$fallback[self::fallbackKey()]);
    }

    private static function fallbackKey(): string
    {
        return (string) getmypid();
    }
}
