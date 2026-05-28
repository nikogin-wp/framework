<?php

namespace Nikogin\Framework\Console\Commands;

use Nikogin\Framework\Console\Command;

class MakeRepository extends Command
{
    private const TYPES = [
        'db'       => [
            'stub'      => 'repository.stub',
            'parent'    => 'Repository',
            'dir'       => 'Db',
            'namespace' => 'Repository\Db',
        ],
        'wp'       => [
            'stub'      => 'wp-repository.stub',
            'parent'    => 'WpRepository',
            'dir'       => 'Wp',
            'namespace' => 'Repository\Wp',
        ],
        'taxonomy' => [
            'stub'      => 'taxonomy-repository.stub',
            'parent'    => 'TaxonomyRepository',
            'dir'       => 'Taxonomy',
            'namespace' => 'Repository\Taxonomy',
        ],
    ];

    public function name(): string
    {
        return 'make:repository';
    }

    public function handle(array $args, array $options, string $basePath): void
    {
        $className = $args[0] ?? null;
        $type      = $options['type'] ?? null;

        if (!$className) {
            $this->error('Class name is required. Usage: make:repository {ClassName} --type={db|wp|taxonomy}');
            exit(1);
        }

        if (!$type) {
            $this->error('--type is required. Available types: ' . implode(', ', array_keys(self::TYPES)));
            exit(1);
        }

        if (!isset(self::TYPES[$type])) {
            $this->error("Unknown type \"{$type}\". Available: " . implode(', ', array_keys(self::TYPES)));
            exit(1);
        }

        $config    = self::TYPES[$type];
        $stubPath  = __DIR__ . '/../../Stubs/' . $config['stub'];
        $stub      = file_get_contents($stubPath);
        $slug      = $this->toSlug($className);
        $pluginNamespace = $this->resolvePluginNamespace($basePath);

        $namespace = $pluginNamespace . '\\' . $config['namespace'];

        $stub = $this->replace($stub, [
            '{{ namespace }}'  => $namespace,
            '{{ class }}'      => $className,
            '{{ table }}'      => $slug,
            '{{ post_type }}'  => $slug,
            '{{ taxonomy }}'   => $slug,
        ]);

        $outputDir  = $basePath . '/app/Repository/' . $config['dir'];
        $outputFile = $outputDir . '/' . $className . '.php';

        if (file_exists($outputFile)) {
            $this->error("File already exists: app/Repository/{$config['dir']}/{$className}.php");
            exit(1);
        }

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        file_put_contents($outputFile, $stub);

        $this->success("Created app/Repository/{$config['dir']}/{$className}.php");
    }

    private function toSlug(string $className): string
    {
        $name = preg_replace('/Repository$/i', '', $className);
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
    }

    private function resolvePluginNamespace(string $basePath): string
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

    private function replace(string $stub, array $replacements): string
    {
        return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }

    private function success(string $message): void
    {
        echo "\033[32m{$message}\033[0m" . PHP_EOL;
    }

    private function error(string $message): void
    {
        fwrite(STDERR, "\033[31mError:\033[0m {$message}" . PHP_EOL);
    }
}
