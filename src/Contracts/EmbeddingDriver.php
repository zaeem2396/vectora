<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Contracts;

/** Pluggable text → vector embedding (OpenAI, local, custom). */
interface EmbeddingDriver
{
    /**
     * @return list<float>
     */
    public function embed(string $text): array;

    /**
     * @param  list<string>  $texts
     * @return list<list<float>>
     */
    public function embedMany(array $texts): array;
}
