<?php

namespace Nikogin\Framework\Abstracts;

use ReflectionClass;
use Nikogin\Framework\Attributes\AsModule;
use Nikogin\Framework\Support\Config;
use Nikogin\Framework\Support\Container;
use Nikogin\Framework\Support\Discovery;

/**
 * Base class for module managers.
 *
 * Extend the concrete ModuleManager in your plugin and register discovered
 * module ServiceProvider classes via registerModule(). If a plugin has no
 * app/Modules/ folder, the concrete class simply never calls registerModule()
 * and register() below no-ops.
 *
 * ```php
 * class AppModuleManager extends ModuleManager
 * {
 *     public function register(): void
 *     {
 *         foreach (glob(...) as $file) {
 *             $this->registerModule($className);
 *         }
 *
 *         parent::register();
 *     }
 * }
 * ```
 */
abstract class ModuleManager
{
    protected array $modules = [];

    public function registerModule(string $providerClass): void
    {
        if (!is_subclass_of($providerClass, ServiceProvider::class) || in_array($providerClass, $this->modules, true)) {
            return;
        }

        $this->modules[] = $providerClass;
    }

    /**
     * Find module ServiceProviders: any ServiceProvider carrying #[AsModule]
     * anywhere inside a module folder (`{modulesDir}/{Module}/**`), not only
     * in `Providers/*ServiceProvider.php`.
     *
     * @param string $modulesDir e.g. app/Modules
     * @param string $namespace  Namespace mapped to $modulesDir, e.g. Nikogin\Modules
     * @return list<array{class: class-string, dir: string}>
     */
    public static function discover(string $modulesDir, string $namespace): array
    {
        $cacheKey = $modulesDir . '|' . $namespace;

        return self::$discovered[$cacheKey] ??= self::scan($modulesDir, $namespace);
    }

    /**
     * Discovery results per request, so ModuleManager, ListenerManager and
     * the route loader don't walk the module tree more than once.
     *
     * @var array<string, list<array{class: class-string, dir: string}>>
     */
    private static array $discovered = [];

    /**
     * @return list<array{class: class-string, dir: string}>
     */
    private static function scan(string $modulesDir, string $namespace): array
    {
        if (!is_dir($modulesDir)) {
            return [];
        }

        $modules = [];

        foreach (glob(rtrim($modulesDir, '/') . '/*', GLOB_ONLYDIR) as $moduleDir) {
            $classes = Discovery::classesWithAttribute(
                $moduleDir,
                rtrim($namespace, '\\') . '\\' . basename($moduleDir),
                AsModule::class,
                [],
                ServiceProvider::class
            );

            foreach ($classes as $class) {
                $modules[] = ['class' => $class, 'dir' => $moduleDir];
            }
        }

        return $modules;
    }

    public function register(): void
    {
        foreach ($this->modules as $providerClass) {
            if (!self::isEnabled($providerClass)) {
                continue;
            }

            Container::bind($providerClass, fn() => new $providerClass());
            $instance = Container::get($providerClass);

            $instance->register();
            $instance->boot();
        }
    }

    /**
     * Whether a module's ServiceProvider is enabled.
     *
     * Resolution order:
     *  1. `Config::get('modules')[name]` — config/modules.php returns
     *     `['modules' => ['crm' => false]]`. Booleans and boolean-like strings
     *     ('false', '0', 'off', ...) are accepted; anything else is ignored.
     *  2. Legacy flat key `Config::get(name)`, only when it is a real bool, so a
     *     module named like a reserved key ('slug', 'namespace', ...) can't be
     *     switched by that unrelated value.
     *  3. The attribute's own `enabled` value.
     *
     * A class with no #[AsModule] attribute is never enabled.
     */
    public static function isEnabled(string $providerClass): bool
    {
        if (!class_exists($providerClass)) {
            return false;
        }

        $reflection = new ReflectionClass($providerClass);
        $attributes = $reflection->getAttributes(AsModule::class);

        if (empty($attributes)) {
            return false;
        }

        /** @var AsModule $module */
        $module = $attributes[0]->newInstance();

        $modules = Config::get('modules', []);

        if (is_array($modules) && array_key_exists($module->name, $modules)) {
            $flag = self::toBool($modules[$module->name]);

            if ($flag !== null) {
                return $flag;
            }
        }

        $legacy = Config::get($module->name);

        return is_bool($legacy) ? $legacy : $module->enabled;
    }

    private static function toBool(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_string($value)) {
            return filter_var(trim((string) $value), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        return null;
    }
}
