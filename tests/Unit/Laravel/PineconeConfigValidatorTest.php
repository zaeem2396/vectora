<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Unit\Laravel;

use PHPUnit\Framework\TestCase;
use Vectora\Pinecone\Laravel\Support\PineconeConfigValidator;

final class PineconeConfigValidatorTest extends TestCase
{
    public function test_accepts_minimal_valid_config(): void
    {
        PineconeConfigValidator::validate([
            'http' => [
                'timeout' => 30.0,
                'connect_timeout' => 10.0,
                'retries' => 4,
            ],
        ]);
        $this->addToAssertionCount(1);
    }

    public function test_rejects_non_positive_timeout(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('pinecone.http.timeout');
        PineconeConfigValidator::validate([
            'http' => ['timeout' => 0, 'connect_timeout' => 1, 'retries' => 1],
        ]);
    }

    public function test_rejects_invalid_eloquent_sync(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('default_sync');
        PineconeConfigValidator::validate([
            'http' => ['timeout' => 1, 'connect_timeout' => 1, 'retries' => 1],
            'eloquent' => ['default_sync' => 'invalid'],
        ]);
    }

    public function test_rejects_negative_query_cache_ttl_when_enabled(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('query_cache.ttl');
        PineconeConfigValidator::validate([
            'http' => ['timeout' => 1, 'connect_timeout' => 1, 'retries' => 1],
            'query_cache' => ['enabled' => true, 'ttl' => -1],
        ]);
    }

    public function test_rejects_non_array_metrics_section(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('pinecone.metrics must be an array');
        PineconeConfigValidator::validate([
            'http' => ['timeout' => 1, 'connect_timeout' => 1, 'retries' => 1],
            'metrics' => 'invalid',
        ]);
    }

    public function test_rejects_non_array_vector_store(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('pinecone.vector_store must be an array');
        PineconeConfigValidator::validate([
            'http' => ['timeout' => 1, 'connect_timeout' => 1, 'retries' => 1],
            'vector_store' => 'invalid',
        ]);
    }

    public function test_rejects_unknown_vector_store_default(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('vector_store.default');
        PineconeConfigValidator::validate([
            'http' => ['timeout' => 1, 'connect_timeout' => 1, 'retries' => 1],
            'vector_store' => ['default' => 'unknown-engine'],
        ]);
    }

    public function test_rejects_non_array_vector_store_drivers(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('vector_store.drivers');
        PineconeConfigValidator::validate([
            'http' => ['timeout' => 1, 'connect_timeout' => 1, 'retries' => 1],
            'vector_store' => ['default' => 'pinecone', 'drivers' => 'nope'],
        ]);
    }

    public function test_rejects_non_array_llm_section(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('pinecone.llm must be an array');
        PineconeConfigValidator::validate([
            'http' => ['timeout' => 1, 'connect_timeout' => 1, 'retries' => 1],
            'llm' => 'nope',
        ]);
    }

    public function test_rejects_non_array_llm_drivers(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('pinecone.llm.drivers');
        PineconeConfigValidator::validate([
            'http' => ['timeout' => 1, 'connect_timeout' => 1, 'retries' => 1],
            'llm' => ['default' => 'stub', 'drivers' => 'bad'],
        ]);
    }

    public function test_rejects_unknown_llm_default(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('pinecone.llm.default');
        PineconeConfigValidator::validate([
            'http' => ['timeout' => 1, 'connect_timeout' => 1, 'retries' => 1],
            'llm' => ['default' => 'unknown-llm', 'drivers' => []],
        ]);
    }

    public function test_rejects_non_array_dx_section(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('pinecone.dx must be an array');
        PineconeConfigValidator::validate([
            'http' => ['timeout' => 1, 'connect_timeout' => 1, 'retries' => 1],
            'dx' => 'invalid',
        ]);
    }
}
