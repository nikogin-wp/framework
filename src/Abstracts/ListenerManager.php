<?php

namespace Nikogin\Framework\Abstracts;

use ReflectionClass;
use Nikogin\Framework\Attributes\AsListener;

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
        if (!is_subclass_of($listenerClass, Listener::class)) {
            return;
        }

        $this->listeners[] = $listenerClass;
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
