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
