<?php

namespace Nikogin\Framework\Console\Commands;

use Nikogin\Framework\Console\Command;

class MakeShortcode extends Command
{
    public function name(): string
    {
        return 'make:shortcode';
    }

    public function handle(array $args, array $options, string $basePath): void
    {
        $className = $args[0] ?? null;

        if (!$className) {
            $this->error('Class name is required. Usage: make:shortcode {ClassName}');
            exit(1);
        }

        $stub      = $this->loadStub('shortcode.stub');
        $namespace = $this->resolvePluginNamespace($basePath) . '\\Shortcodes';
        $tag       = $options['name'] ?? $this->toSnakeCase($className, 'Shortcode');

        $stub = $this->replace($stub, [
            '{{ namespace }}' => $namespace,
            '{{ class }}'     => $className,
            '{{ tag }}'       => $tag,
        ]);

        $outputDir  = $basePath . '/app/Shortcodes';
        $outputFile = $outputDir . '/' . $className . '.php';

        if (file_exists($outputFile)) {
            $this->error("File already exists: app/Shortcodes/{$className}.php");
            exit(1);
        }

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        file_put_contents($outputFile, $stub);

        $this->success("Created app/Shortcodes/{$className}.php");
    }
}
