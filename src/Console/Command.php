<?php

namespace Nikogin\Framework\Console;

abstract class Command
{
    abstract public function name(): string;

    abstract public function handle(array $args, array $options, string $basePath): void;

    protected function loadStub(string $stubName): string
    {
        return file_get_contents(__DIR__ . '/../Stubs/' . $stubName);
    }

    protected function resolvePluginNamespace(string $basePath): string
    {
        $composerFile = $basePath . '/composer.json';

        if (!file_exists($composerFile)) {
            return 'Nikogin';
        }

        $composer = json_decode(file_get_contents($composerFile), true);
        $psr4     = $composer['autoload']['psr-4'] ?? [];

        foreach ($psr4 as $ns => $path) {
            if (str_starts_with($path, 'app') || $path === 'app/') {
                return rtrim($ns, '\\');
            }
        }

        return 'Nikogin';
    }

    protected function replace(string $stub, array $replacements): string
    {
        return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }

    protected function toSnakeCase(string $className, string $stripSuffix = ''): string
    {
        $name = $stripSuffix
            ? preg_replace('/' . preg_quote($stripSuffix, '/') . '$/i', '', $className)
            : $className;

        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
    }

    protected function success(string $message): void
    {
        echo "\033[32m{$message}\033[0m" . PHP_EOL;
    }

    protected function error(string $message): void
    {
        fwrite(STDERR, "\033[31mError:\033[0m {$message}" . PHP_EOL);
    }
}
