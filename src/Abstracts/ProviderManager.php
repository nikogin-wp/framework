<?php

namespace Nikogin\Framework\Abstracts;

use Nikogin\Framework\Support\Updater;

/**
 * Base class for provider managers.
 *
 * Extend this class in your plugin and define $providers as a keyed array
 * where the key is the provider class and the value is the priority.
 * Lower priority number runs first (same convention as WordPress hooks).
 *
 * ```php
 * class AppProviderManager extends ProviderManager
 * {
 *     protected array $providers = [
 *         MigrationProvider::class  => 1,   // runs first
 *         AppServiceProvider::class => 10,
 *     ];
 * }
 * ```
 *
 * A flat list without priorities is also accepted — defaults to priority 10.
 */
abstract class ProviderManager
{
    protected array $providers = [];

    public function register(): void
    {
        $sorted = [];
        foreach ($this->providers as $key => $value) {
            if (is_int($key)) {
                $sorted[] = ['class' => $value, 'priority' => 10];
            } else {
                $sorted[] = ['class' => $key, 'priority' => $value];
            }
        }

        usort($sorted, fn($a, $b) => $a['priority'] <=> $b['priority']);

        foreach ($sorted as $item) {
            (new $item['class']())->register();
        }

        // Version is saved after all providers (including migrations) have run
        Updater::saveVersion();
    }
}