<?php

namespace Nikogin\Framework\Support;

use FilesystemIterator;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;

/**
 * Attribute-based class discovery.
 *
 * Recursively walks a PSR-4 directory, maps each file to its class name
 * (`{namespace}\{relative\path}\{File}`) and returns the concrete classes
 * that carry a given attribute.
 *
 * Only files that look like PSR-4 classes are autoloaded: every path segment
 * must start with an uppercase letter and the file must declare a class with
 * the same name. Lowercase directories such as a module's `routes/` and plain
 * PHP scripts are never loaded, so they cannot run by accident.
 *
 * ```php
 * Discovery::classesWithAttribute(NIKOGIN_DIR . 'app', 'Nikogin', AsListener::class);
 * ```
 */
class Discovery
{
    /**
     * @param string        $directory    PSR-4 root (or a subfolder of it) to scan
     * @param string        $namespace    Namespace that maps to $directory
     * @param class-string  $attribute    Attribute class the discovered classes must carry
     * @param string[]      $excludeDirs  Absolute directories to skip (e.g. app/Modules)
     * @param class-string|null $subclassOf Only return subclasses of this class/interface
     * @return list<class-string>
     */
    public static function classesWithAttribute(
        string $directory,
        string $namespace,
        string $attribute,
        array $excludeDirs = [],
        ?string $subclassOf = null,
    ): array {
        $classes = [];

        $shortName = substr(strrchr('\\' . $attribute, '\\'), 1);

        foreach (self::classes($directory, $namespace, $excludeDirs, $shortName) as $class) {
            $reflection = new ReflectionClass($class);

            if (!$reflection->isInstantiable() || empty($reflection->getAttributes($attribute))) {
                continue;
            }

            if ($subclassOf !== null && !is_subclass_of($class, $subclassOf)) {
                continue;
            }

            $classes[] = $class;
        }

        return $classes;
    }

    /**
     * All loadable class names under a PSR-4 directory, sorted for a stable order.
     *
     * @param string[]    $excludeDirs
     * @param string|null $mustContain Only autoload files whose source contains this
     *                                 string (keeps unrelated classes — e.g. ones
     *                                 extending admin-only WP classes — unloaded)
     * @return list<class-string>
     */
    public static function classes(
        string $directory,
        string $namespace,
        array $excludeDirs = [],
        ?string $mustContain = null,
    ): array
    {
        $directory = rtrim($directory, '/\\');

        if (!is_dir($directory)) {
            return [];
        }

        $exclude = array_filter(array_map(
            fn(string $dir) => realpath($dir) ?: null,
            $excludeDirs
        ));

        $iterator = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(
                new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
                function (SplFileInfo $file) use ($exclude): bool {
                    if ($file->isDir()) {
                        return self::isClassSegment($file->getFilename())
                            && !in_array($file->getRealPath(), $exclude, true);
                    }

                    return $file->getExtension() === 'php'
                        && self::isClassSegment($file->getBasename('.php'));
                }
            )
        );

        $namespace = trim($namespace, '\\');
        $classes   = [];

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            $relative = substr($file->getPathname(), strlen($directory) + 1, -4);
            $class    = $namespace . '\\' . str_replace(['/', '\\'], '\\', $relative);

            if (!self::declaresClass($file->getPathname(), $file->getBasename('.php'), $mustContain)) {
                continue;
            }

            if (class_exists($class)) {
                $classes[] = $class;
            }
        }

        sort($classes);

        return $classes;
    }

    private static function isClassSegment(string $name): bool
    {
        return (bool) preg_match('/^[A-Z][A-Za-z0-9_]*$/', $name);
    }

    /**
     * Cheap guard so a file is only autoloaded when it really defines the class
     * its filename promises.
     */
    private static function declaresClass(string $path, string $shortName, ?string $mustContain): bool
    {
        $contents = @file_get_contents($path);

        if ($contents === false || ($mustContain !== null && !str_contains($contents, $mustContain))) {
            return false;
        }

        return (bool) preg_match(
            '/(?<![:$\w])class\s+' . preg_quote($shortName, '/') . '\b/i',
            $contents
        );
    }
}
