<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Laravel;

use Vectora\Pinecone\Laravel\Embeddings\LLMManager;
use Vectora\Pinecone\LLM\OpenAILLMDriver;
use Vectora\Pinecone\LLM\StubLLMDriver;

final class LLMDriverFactoryNormalizationTest extends PineconeFeatureTestCase
{
    public function test_default_driver_name_is_case_and_whitespace_insensitive(): void
    {
        $this->mergePineconeConfig([
            'llm' => [
                'default' => ' OpenAI ',
                'drivers' => [
                    'openai' => ['api_key' => 'test-key'],
                ],
            ],
        ]);

        $driver = $this->app->make(LLMManager::class)->driver();
        $this->assertInstanceOf(OpenAILLMDriver::class, $driver);
    }

    public function test_explicit_driver_argument_is_normalized(): void
    {
        $this->mergePineconeConfig([
            'llm' => [
                'default' => 'openai',
                'drivers' => [
                    'stub' => [],
                    'openai' => ['api_key' => 'k'],
                ],
            ],
        ]);

        $stub = $this->app->make(LLMManager::class)->driver(' STUB ');
        $this->assertInstanceOf(StubLLMDriver::class, $stub);
    }
}
