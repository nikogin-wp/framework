<?php

namespace Nikogin\Framework\Support;

class View
{
    private static string $basePath = '';

    public static function setBasePath(string $path): void
    {
        self::$basePath = rtrim($path, '/');
    }

    /**
     * Load a view using dot notation relative to the base path.
     * 'example.example' → {resources_path}/example/example.php
     */
    public static function load(string $view, array $args = []): void
    {
        $base = self::$basePath !== '' ? self::$basePath : rtrim(Config::get('resources_path', ''), '/');
        $path = $base . '/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($path)) {
            return;
        }

        (static function (string $__path, array $__args) {
            extract($__args, EXTR_SKIP);
            require $__path;
        })($path, $args);
    }

    /**
     * Load all PHP files from a directory, injecting the same variables into each.
     */
    public static function loadDir(string $directory, array $args = []): void
    {
        foreach (glob(rtrim($directory, '/') . '/*.php') as $file) {
            (static function (string $__path, array $__args) {
                extract($__args, EXTR_SKIP);
                require $__path;
            })($file, $args);
        }
    }
}
