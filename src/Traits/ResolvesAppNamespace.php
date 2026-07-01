<?php

namespace Nikogin\Framework\Traits;

/**
 * Used by the app-level managers (ProviderManager, ListenerManager,
 * ModuleManager) that glob a directory and rebuild a class's FQCN from its
 * filename. Those managers live in the plugin's own root namespace (e.g.
 * `Nikogin\Managers\ProviderManager`), so the root segment of that namespace
 * *is* the plugin's PSR-4 root — no need to hardcode it separately or keep
 * it in sync with composer.json by hand.
 */
trait ResolvesAppNamespace
{
    protected function appNamespace(): string
    {
        return strstr(static::class, '\\', true) ?: static::class;
    }
}
