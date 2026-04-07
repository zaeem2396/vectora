<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel\Commands;

use Illuminate\Console\Command;
use Vectora\Pinecone\Contracts\Embeddable;

/**
 * Phase 11 DX: print semantic search matches as JSON (for local debugging).
 */
final class SemanticDebugCommand extends Command
{
    protected $signature = 'pinecone:semantic-debug
                            {model : Embeddable model class (FQCN or short name under App\\Models)}
                            {query : Natural language query}
                            {--top=10 : Vector topK}';

    protected $description = 'Run Embeddable::semanticSearch() and print JSON match summary (enable pinecone.dx.semantic_debug)';

    public function handle(): int
    {
        if (! (bool) config('pinecone.dx.semantic_debug', false)) {
            $this->error('Semantic debug is disabled. Set VECTORA_SEMANTIC_DEBUG=true or pinecone.dx.semantic_debug in config.');

            return self::FAILURE;
        }

        $raw = (string) $this->argument('model');
        $class = class_exists($raw) ? $raw : trim($this->laravel->getNamespace(), '\\').'\\Models\\'.$raw;
        if (! class_exists($class)) {
            $this->error('Model class not found: '.$raw);

            return self::FAILURE;
        }

        if (! is_subclass_of($class, Embeddable::class)) {
            $this->error($class.' must implement '.Embeddable::class);

            return self::FAILURE;
        }

        $topK = (int) $this->option('top');
        if ($topK < 1) {
            $this->error('Option --top must be at least 1.');

            return self::FAILURE;
        }

        $queryText = (string) $this->argument('query');
        /** @var class-string<Embeddable> $class */
        $result = $class::semanticSearch($queryText, $topK);

        $payload = [
            'model' => $class,
            'query' => $queryText,
            'topK' => $topK,
            'namespace' => $class::vectorEmbeddingNamespace(),
            'matches' => array_map(
                static fn ($m): array => ['id' => $m->id, 'score' => $m->score],
                $result->matches,
            ),
        ];
        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        return self::SUCCESS;
    }
}
