<?php

namespace Nikogin\Framework\Support;

class Resources
{
    /**
     * Load a single PHP file, optionally injecting variables into its scope.
     */
    public static function load(string $path, array $args = []): void
    {
        if (!file_exists($path)) {
            return;
        }

        (static function (string $__path, array $__args) {
            extract($__args, EXTR_SKIP);
            require $__path;
        })($path, $args);
    }
}
