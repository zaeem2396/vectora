<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Core\Exception;

/** Non-retryable or final API error after retries. */
class ApiException extends PineconeException
{
    public function __construct(
        string $message,
        public readonly int $statusCode,
        public readonly ?string $responseBody = null,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function isRateLimited(): bool
    {
        return $this->statusCode === 429;
    }

    public function category(): ApiErrorCategory
    {
        return ApiErrorCategory::fromStatusCode($this->statusCode);
    }

    public function isAuthenticationError(): bool
    {
        return $this->statusCode === 401 || $this->statusCode === 403;
    }

    public function isNotFound(): bool
    {
        return $this->statusCode === 404;
    }

    public function isClientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode <= 499;
    }

    public function isServerError(): bool
    {
        return $this->statusCode >= 500 && $this->statusCode <= 599;
    }
}
