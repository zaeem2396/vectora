<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Laravel\Support;

trait InteractsWithPineconeConfig
{
    /**
     * @param  array<string, mixed>  $config
     * @return array<string, array{host: string, namespace: string}>
     */
    protected function resolveIndexes(array $config): array
    {
        /** @var array<string, array<string, mixed>> $indexes */
        $indexes = $config['indexes'] ?? [];

        if ($indexes === [] && (($config['host'] ?? '') !== '' || ($config['namespace'] ?? '') !== '')) {
            $legacyKey = (string) ($config['default'] ?? 'default');
            $indexes = [
                $legacyKey => [
                    'host' => (string) ($config['host'] ?? ''),
                    'namespace' => (string) ($config['namespace'] ?? ''),
                ],
            ];
        }

        $normalized = [];
        foreach ($indexes as $name => $entry) {
            if (! is_string($name) || ! is_array($entry)) {
                continue;
            }
            $normalized[$name] = [
                'host' => (string) ($entry['host'] ?? ''),
                'namespace' => (string) ($entry['namespace'] ?? ''),
            ];
        }

        // Published configs without `indexes` still merge package `indexes.default` via mergeConfigFrom,
        // leaving empty env-based hosts. Prefer legacy root `host` / `namespace` for the default index.
        $defaultKey = (string) ($config['default'] ?? 'default');
        $rootHost = (string) ($config['host'] ?? '');
        $rootNamespace = (string) ($config['namespace'] ?? '');

        if ($rootHost !== '' && isset($normalized[$defaultKey]) && $normalized[$defaultKey]['host'] === '') {
            $normalized[$defaultKey]['host'] = $rootHost;
        }

        if (isset($normalized[$defaultKey]) && $normalized[$defaultKey]['namespace'] === '' && $rootNamespace !== '') {
            $normalized[$defaultKey]['namespace'] = $rootNamespace;
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{host: string, namespace: string}
     */
    protected function indexConnection(array $config, ?string $name): array
    {
        $indexes = $this->resolveIndexes($config);
        $defaultName = (string) ($config['default'] ?? 'default');
        $key = $name ?? $defaultName;

        if (! isset($indexes[$key])) {
            throw new \InvalidArgumentException(sprintf('Unknown Pinecone index connection [%s].', $key));
        }

        return $indexes[$key];
    }
}
