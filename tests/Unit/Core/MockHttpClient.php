<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Core;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class MockHttpClient implements ClientInterface
{
    /** @var list<ResponseInterface> */
    public array $responses = [];

    /** @var list<RequestInterface> */
    public array $requests = [];

    public ?\Throwable $throwOnSend = null;

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;
        if ($this->throwOnSend !== null) {
            throw $this->throwOnSend;
        }
        $r = array_shift($this->responses);
        if ($r === null) {
            throw new \RuntimeException('MockHttpClient: no response queued.');
        }

        return $r;
    }
}
