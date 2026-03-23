<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Embeddings;

use Vectora\Pinecone\Contracts\EmbeddingDriver;

abstract class AbstractEmbeddingDriver implements EmbeddingDriver
{
    public function embedMany(array $texts): array
    {
        $out = [];
        foreach ($texts as $t) {
            if (! is_string($t)) {
                throw new \InvalidArgumentException('embedMany expects a list of strings.');
            }
            $out[] = $this->embed($t);
        }

        return $out;
    }
}
