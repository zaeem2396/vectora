<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Core\Http;

final readonly class RetryPolicy
{
    public function __construct(
        public int $maxAttempts = 4,
        public int $initialDelayMs = 250,
        public float $multiplier = 2.0,
        public int $maxDelayMs = 10_000,
        public bool $respectRetryAfter = true,
    ) {
        if ($maxAttempts < 1) {
            throw new \InvalidArgumentException('maxAttempts must be at least 1.');
        }
    }

    public function delayMsForAttempt(int $attemptIndex, ?int $retryAfterSeconds = null): int
    {
        if ($retryAfterSeconds !== null && $this->respectRetryAfter) {
            return min($this->maxDelayMs, max(0, $retryAfterSeconds) * 1000);
        }
        $base = (int) ($this->initialDelayMs * ($this->multiplier ** max(0, $attemptIndex - 1)));

        return min($this->maxDelayMs, $base);
    }
}
