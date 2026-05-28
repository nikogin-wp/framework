<?php

namespace Nikogin\Framework\Console\Commands;

use Nikogin\Framework\Console\Command;

class MakeProvider extends Command
{
    public function name(): string
    {
        return 'make:provider';
    }

    public function handle(array $args, array $options, string $basePath): void
    {
        $className = $args[0] ?? null;

        if (!$className) {
            $this->error('Class name is required. Usage: make:provider {ClassName}');
            exit(1);
        }

        $stub      = $this->loadStub('provider.stub');
        $namespace = $this->resolvePluginNamespace($basePath) . '\\Providers';

        $stub = $this->replace($stub, [
            '{{ namespace }}' => $namespace,
            '{{ class }}'     => $className,
        ]);

        $outputDir  = $basePath . '/app/Providers';
        $outputFile = $outputDir . '/' . $className . '.php';

        if (file_exists($outputFile)) {
            $this->error("File already exists: app/Providers/{$className}.php");
            exit(1);
        }

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        file_put_contents($outputFile, $stub);

        $this->success("Created app/Providers/{$className}.php");
    }
}
