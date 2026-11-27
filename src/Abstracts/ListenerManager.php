<?php

namespace Nikogin\Framework\Abstracts;

use ReflectionClass;
use Nikogin\Framework\Attributes\AsListener;
use Nikogin\Framework\Support\Discovery;

/**
 * Base class for listener managers.
 *
 * Extend the concrete ListenerManager in your plugin and define $listeners.
 *
 * ```php
 * class AppListenerManager extends ListenerManager
 * {
 *     protected array $listeners = [
 *         MyActionListener::class,
 *         MyFilterListener::class,
 *     ];
 * }
 * ```
 */
abstract class ListenerManager
{
    protected array $listeners = [];

    public function registerListener(string $listenerClass): void
    {
        if (!is_subclass_of($listenerClass, Listener::class) || in_array($listenerClass, $this->listeners, true)) {
            return;
        }

        $this->listeners[] = $listenerClass;
    }

    /**
     * Recursively discover every Listener with #[AsListener] under $directory
     * (any depth, e.g. app/Listeners/Action, app/Services/Foo/Listeners, ...).
     *
     * @param string   $directory   PSR-4 directory to scan
     * @param string   $namespace   Namespace mapped to $directory
     * @param string[] $excludeDirs Directories to skip, e.g. disabled modules
     */
    public function discover(string $directory, string $namespace, array $excludeDirs = []): void
    {
        $classes = Discovery::classesWithAttribute(
            $directory,
            $namespace,
            AsListener::class,
            $excludeDirs,
            Listener::class
        );

        foreach ($classes as $class) {
            $this->registerListener($class);
        }
    }

    public function register(): void
    {
        foreach ($this->listeners as $listenerClass) {
            $reflection = new ReflectionClass($listenerClass);
            $attributes = $reflection->getAttributes(AsListener::class);

            if (empty($attributes)) {
                continue;
            }

            /** @var AsListener $config */
            $config = $attributes[0]->newInstance();

            $listenerInstance = new $listenerClass();

            if ($config->type === 'action') {
                if (!has_action($config->name, [$listenerInstance, 'handle'])) {
                    add_action($config->name, [$listenerInstance, 'handle'], $config->priority, $config->argsCount);
                }
            } elseif ($config->type === 'filter') {
                if (!has_filter($config->name, [$listenerInstance, 'handle'])) {
                    add_filter($config->name, [$listenerInstance, 'handle'], $config->priority, $config->argsCount);
                }
            }
        }
    }
}
