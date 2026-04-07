<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;
use Vectora\Pinecone\Eloquent\AbstractEmbeddableModel;

/**
 * Phase 11 DX: scaffold an {@see AbstractEmbeddableModel} subclass.
 */
final class MakeVectorModelCommand extends GeneratorCommand
{
    protected $name = 'make:vector-model';

    protected $description = 'Create a new Embeddable Eloquent model (Vectora / HasEmbeddings)';

    /** @var string */
    protected $type = 'Model';

    protected function getStub(): string
    {
        $path = dirname(__DIR__, 3).'/stubs/vector-model.stub';
        if (! is_file($path)) {
            throw new \RuntimeException('Vector model stub not found at '.$path);
        }

        return $path;
    }

    /**
     * @param  string  $rootNamespace
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return is_string($rootNamespace) ? $rootNamespace.'\\Models' : 'App\\Models';
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: int, 3: string}>
     */
    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the model already exists'],
        ];
    }
}
