<?php

namespace Nikogin\Framework\Console\Commands;

use Nikogin\Framework\Console\Command;

class MakeModule extends Command
{
    private const SUBDIRS = ['Controllers', 'Repository', 'Listeners', 'Providers', 'routes'];

    public function name(): string
    {
        return 'make:module';
    }

    public function handle(array $args, array $options, string $basePath): void
    {
        $className = $args[0] ?? null;

        if (!$className) {
            $this->error('Class name is required. Usage: make:module {ModuleName}');
            exit(1);
        }

        $moduleDir = $basePath . '/app/Modules/' . $className;

        if (is_dir($moduleDir)) {
            $this->error("Module already exists: app/Modules/{$className}");
            exit(1);
        }

        $moduleNamespace = $this->resolvePluginNamespace($basePath) . '\\Modules\\' . $className;
        $moduleName       = $this->toSnakeCase($className);

        $providerStub = $this->replace($this->loadStub('module-service-provider.stub'), [
            '{{ namespace }}'   => $moduleNamespace . '\\Providers',
            '{{ class }}'       => $className . 'ServiceProvider',
            '{{ module_name }}' => $moduleName,
        ]);

        $routesStub = $this->replace($this->loadStub('module-routes.stub'), [
            '{{ module_name }}' => $moduleName,
        ]);

        foreach (self::SUBDIRS as $subdir) {
            mkdir($moduleDir . '/' . $subdir, 0755, true);
            file_put_contents($moduleDir . '/' . $subdir . '/.gitkeep', '');
        }

        file_put_contents($moduleDir . '/routes/routes.php', $routesStub);
        file_put_contents($moduleDir . '/Providers/' . $className . 'ServiceProvider.php', $providerStub);

        $this->success("Created app/Modules/{$className}");
    }
}
