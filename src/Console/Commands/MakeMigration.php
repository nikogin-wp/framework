<?php

namespace Nikogin\Framework\Console\Commands;

use Nikogin\Framework\Console\Command;

class MakeMigration extends Command
{
    public function name(): string
    {
        return 'make:migration';
    }

    public function handle(array $args, array $options, string $basePath): void
    {
        $className = $args[0] ?? null;

        if (!$className) {
            $this->error('Class name is required. Usage: make:migration {ClassName}');
            exit(1);
        }

        $stub      = $this->loadStub('migration.stub');
        $namespace = $this->resolvePluginNamespace($basePath) . '\\Migrations';
        $table     = $this->toTableName($className);

        $stub = $this->replace($stub, [
            '{{ namespace }}' => $namespace,
            '{{ class }}'     => $className,
            '{{ table }}'     => $table,
        ]);

        $outputDir  = $basePath . '/app/Migrations';
        $outputFile = $outputDir . '/' . $className . '.php';

        if (file_exists($outputFile)) {
            $this->error("File already exists: app/Migrations/{$className}.php");
            exit(1);
        }

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        file_put_contents($outputFile, $stub);

        $this->success("Created app/Migrations/{$className}.php");
    }

    private function toTableName(string $className): string
    {
        $name = preg_replace('/^Create/i', '', $className);
        $name = preg_replace('/Table$/i', '', $name);

        return $this->toSnakeCase($name);
    }
}
