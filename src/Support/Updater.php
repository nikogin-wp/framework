<?php

namespace Nikogin\Framework\Support;

class Updater
{
    public static function needsUpdate(): bool
    {
        $slug      = Config::get('slug');
        $version   = Config::get('version');
        $installed = get_option($slug . '_version');

        return !$installed || version_compare($installed, $version, '<');
    }

    public static function saveVersion(): void
    {
        update_option(Config::get('slug') . '_version', Config::get('version'));
    }
}