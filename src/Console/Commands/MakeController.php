<?php

namespace Nikogin\Framework\Console\Commands;

use Nikogin\Framework\Console\Command;

class MakeController extends Command
{
    private const TYPES = [
        'api'     => [
            'stub'      => 'api-controller.stub',
            'dir'       => 'Controllers/Api',
            'namespace' => 'Controllers\Api',
        ],
        'menu'    => [
            'stub'      => 'menu-controller.stub',
            'dir'       => 'Controllers/Dashboards/Menu',
            'namespace' => 'Controllers\Dashboards\Menu',
        ],
        'submenu' => [
            'stub'      => 'submenu-controller.stub',
            'dir'       => 'Controllers/Dashboards/Submenu',
            'namespace' => 'Controllers\Dashboards\Submenu',
        ],
    ];

    public function name(): string
    {
        return 'make:controller';
    }

    public function handle(array $args, array $options, string $basePath): void
    {
        $className = $args[0] ?? null;
        $type      = $options['type'] ?? null;

        if (!$className) {
            $this->error('Class name is required. Usage: make:controller {ClassName} --type={api|menu|submenu}');
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
        $namespace = $this->resolvePluginNamespace($basePath) . '\\' . $config['namespace'];
        $slug      = $this->toSnakeCase($className, 'Controller');
        $title     = $this->toTitle($className, 'Controller');

        $stub = $this->replace($stub, [
            '{{ namespace }}'   => $namespace,
            '{{ class }}'       => $className,
            '{{ slug }}'        => $slug,
            '{{ title }}'       => $title,
            '{{ parent_slug }}' => $options['parent'] ?? '',
        ]);

        $outputDir  = $basePath . '/app/' . $config['dir'];
        $outputFile = $outputDir . '/' . $className . '.php';

        if (file_exists($outputFile)) {
            $this->error("File already exists: app/{$config['dir']}/{$className}.php");
            exit(1);
        }

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        file_put_contents($outputFile, $stub);

        $this->success("Created app/{$config['dir']}/{$className}.php");
    }

    private function toTitle(string $className, string $stripSuffix): string
    {
        $name = preg_replace('/' . preg_quote($stripSuffix, '/') . '$/i', '', $className);
        return trim(preg_replace('/(?<!^)[A-Z]/', ' $0', $name));
    }
}
