<?php

namespace Nikogin\Framework\Console\Commands;

use Nikogin\Framework\Console\Command;

class MakeListener extends Command
{
    private const TYPES = [
        'action' => [
            'stub' => 'action-listener.stub',
            'dir'  => 'Action',
        ],
        'filter' => [
            'stub' => 'filter-listener.stub',
            'dir'  => 'Filter',
        ],
    ];

    public function name(): string
    {
        return 'make:listener';
    }

    public function handle(array $args, array $options, string $basePath): void
    {
        $className = $args[0] ?? null;
        $type      = $options['type'] ?? null;

        if (!$className) {
            $this->error('Class name is required. Usage: make:listener {ClassName} --type={action|filter}');
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
        $stub      = $this->loadStub($config['stub']);
        $namespace = $this->resolvePluginNamespace($basePath) . '\\Listeners\\' . $config['dir'];
        $hook      = $options['name'] ?? $this->toSnakeCase($className, 'Listener');

        $stub = $this->replace($stub, [
            '{{ namespace }}' => $namespace,
            '{{ class }}'     => $className,
            '{{ hook }}'      => $hook,
        ]);

        $outputDir  = $basePath . '/app/Listeners/' . $config['dir'];
        $outputFile = $outputDir . '/' . $className . '.php';

        if (file_exists($outputFile)) {
            $this->error("File already exists: app/Listeners/{$config['dir']}/{$className}.php");
            exit(1);
        }

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        file_put_contents($outputFile, $stub);

        $this->success("Created app/Listeners/{$config['dir']}/{$className}.php");
    }
}
