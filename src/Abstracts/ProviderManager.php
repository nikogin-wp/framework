<?php

namespace Nikogin\Framework\Abstracts;

use Nikogin\Framework\Support\Discovery;
use Nikogin\Framework\Support\Updater;

/**
 * Base class for provider managers.
 *
 * Providers are collected in $providers — either discovered from a folder via
 * discover(), or listed by hand. A keyed entry pins the priority, a plain entry
 * uses the provider's own priority(). Lower numbers run first (same convention
 * as WordPress hooks).
 *
 * ```php
 * class AppProviderManager extends ProviderManager
 * {
 *     protected array $providers = [
 *         MigrationProvider::class  => 1,   // runs first
 *         AppServiceProvider::class,        // uses AppServiceProvider::priority()
 *     ];
 * }
 * ```
 *
 * After all providers (including migration providers) have run, the plugin
 * version is stored so migrations run once per version, not on every request.
 */
abstract class ProviderManager
{
    /** @var array<int|class-string, class-string|int> */
    protected array $providers = [];

    /**
     * Add every concrete Provider found under $directory (recursively).
     */
    public function discover(string $directory, string $namespace): void
    {
        foreach (Discovery::classes($directory, $namespace) as $class) {
            if (!is_subclass_of($class, Provider::class) || $this->has($class)) {
                continue;
            }

            if ((new \ReflectionClass($class))->isInstantiable()) {
                $this->providers[] = $class;
            }
        }
    }

    public function register(): void
    {
        $items = [];

        foreach ($this->providers as $key => $value) {
            [$class, $priority] = is_int($key) ? [$value, null] : [$key, $value];

            $instance = new $class();
            $items[]  = [
                'instance' => $instance,
                'priority' => $priority ?? $instance->priority(),
            ];
        }

        usort($items, fn($a, $b) => $a['priority'] <=> $b['priority']);

        $needsUpdate = Updater::needsUpdate();

        foreach ($items as $item) {
            $item['instance']->register();
        }

        // Saved only after every provider (migrations included) ran without throwing.
        if ($needsUpdate) {
            Updater::saveVersion();
        }
    }

    private function has(string $class): bool
    {
        return in_array($class, $this->providers, true) || array_key_exists($class, $this->providers);
    }
}
