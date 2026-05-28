<?php

namespace Nikogin\Framework\Console\Commands;

use Nikogin\Framework\Console\Command;

class MakeJob extends Command
{
    public function name(): string
    {
        return 'make:job';
    }

    public function handle(array $args, array $options, string $basePath): void
    {
        $className = $args[0] ?? null;

        if (!$className) {
            $this->error('Class name is required. Usage: make:job {ClassName}');
            exit(1);
        }

        $stub      = $this->loadStub('job.stub');
        $namespace = $this->resolvePluginNamespace($basePath) . '\\Jobs';
        $hook      = $options['name'] ?? $this->toSnakeCase($className, 'Job');

        $stub = $this->replace($stub, [
            '{{ namespace }}' => $namespace,
            '{{ class }}'     => $className,
            '{{ hook }}'      => $hook,
        ]);

        $outputDir  = $basePath . '/app/Jobs';
        $outputFile = $outputDir . '/' . $className . '.php';

        if (file_exists($outputFile)) {
            $this->error("File already exists: app/Jobs/{$className}.php");
            exit(1);
        }

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        file_put_contents($outputFile, $stub);

        $this->success("Created app/Jobs/{$className}.php");
    }
}
