<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel\Commands;

use Illuminate\Console\Command;
use Vectora\Pinecone\DTO\DeleteVectorsRequest;
use Vectora\Pinecone\Laravel\PineconeClientFactory;

final class PineconeFlushCommand extends Command
{
    protected $signature = 'pinecone:flush
                            {--index= : Logical index name (config pinecone.indexes)}
                            {--namespace= : Namespace to flush (omit for default from config)}
                            {--force : Required in production}';

    protected $description = 'Delete all vectors in a namespace (deleteAll) for the configured index';

    public function handle(PineconeClientFactory $factory): int
    {
        if ($this->laravel->environment('production') && ! $this->option('force')) {
            $this->error('Refusing to flush in production without --force.');

            return self::FAILURE;
        }

        $index = $this->option('index') ? (string) $this->option('index') : null;
        $nsOpt = $this->option('namespace');
        $namespace = $nsOpt !== null && $nsOpt !== false ? (string) $nsOpt : null;

        if ($namespace === null) {
            $c = config('pinecone', []);
            $connName = $index ?? (string) ($c['default'] ?? 'default');
            $indexes = $c['indexes'] ?? [];
            if (isset($indexes[$connName]) && is_array($indexes[$connName])) {
                $nsCfg = $indexes[$connName]['namespace'] ?? '';
                $namespace = is_string($nsCfg) && $nsCfg !== '' ? $nsCfg : null;
            }
        }

        $this->info(sprintf(
            'Flushing namespace [%s] on index [%s]…',
            $namespace ?? '(default)',
            $index ?? (string) (config('pinecone.default') ?? 'default')
        ));

        $factory->vectorStore($index)->delete(new DeleteVectorsRequest(
            namespace: $namespace,
            deleteAll: true,
        ));

        $this->info('Flush request sent.');

        return self::SUCCESS;
    }
}
