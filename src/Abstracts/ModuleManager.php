<?php

namespace Nikogin\Framework\Abstracts;

use ReflectionClass;
use Nikogin\Framework\Attributes\AsModule;
use Nikogin\Framework\Support\Config;
use Nikogin\Framework\Support\Container;

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
        if (!is_subclass_of($providerClass, ServiceProvider::class)) {
            return;
        }

        $this->modules[] = $providerClass;
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
     * Whether a module's ServiceProvider is enabled — checks config/modules.php
     * first (keyed by the #[AsModule] name), then falls back to the attribute's
     * own `enabled` value. A class with no #[AsModule] attribute is never enabled.
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

        return Config::get($module->name) ?? $module->enabled;
    }
}
