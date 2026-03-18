<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Core\Observability;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * Optional hooks for logging / tracing without coupling Core to PSR-3.
 *
 * @phpstan-type HookBefore callable(RequestInterface): void
 * @phpstan-type HookAfter callable(RequestInterface, ResponseInterface): void
 * @phpstan-type HookError callable(RequestInterface, Throwable): void
 */
final class ObservabilityHooks
{
    /**
     * @param  (callable(RequestInterface): void)|null  $beforeRequest
     * @param  (callable(RequestInterface, ResponseInterface): void)|null  $afterResponse
     * @param  (callable(RequestInterface, Throwable): void)|null  $onError
     */
    public function __construct(
        private $beforeRequest = null,
        private $afterResponse = null,
        private $onError = null,
    ) {}

    public function beforeRequest(RequestInterface $request): void
    {
        if ($this->beforeRequest !== null) {
            ($this->beforeRequest)($request);
        }
    }

    public function afterResponse(RequestInterface $request, ResponseInterface $response): void
    {
        if ($this->afterResponse !== null) {
            ($this->afterResponse)($request, $response);
        }
    }

    public function onError(RequestInterface $request, Throwable $e): void
    {
        if ($this->onError !== null) {
            ($this->onError)($request, $e);
        }
    }
}
