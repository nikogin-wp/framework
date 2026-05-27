<?php

namespace Nikogin\Framework\Support;

/**
 * Central configuration store for the framework.
 *
 * Each plugin sets its own values once during bootstrap (in the main plugin file,
 * before any framework class is used). Framework internals such as Router and
 * Updater read from here instead of relying on plugin-specific constants.
 *
 * ## Setup
 *
 * ```php
 * // my-plugin.php
 * use Nikogin\Framework\Support\Config;
 *
 * Config::set([
 *     'namespace' => 'my-plugin/v1',    // REST API namespace
 *     'slug'      => 'my-plugin',       // used for DB option keys
 *     'version'   => MY_PLUGIN_VERSION, // used by Updater
 * ]);
 * ```
 *
 * ## Reserved keys
 *
 * | Key         | Used by  | Example            |
 * |-------------|----------|--------------------|
 * | namespace   | Router   | 'my-plugin/v1'     |
 * | slug        | Updater  | 'my-plugin'        |
 * | version     | Updater  | '1.0.0'            |
 *
 * Any additional plugin-specific keys are allowed and ignored by the framework.
 */
class Config
{
    private static array $config = [];

    /**
     * Merge values into the config store.
     *
     * Safe to call multiple times — later values override earlier ones for the same key.
     *
     * @param array<string, mixed> $config
     */
    public static function set(array $config): void
    {
        self::$config = array_merge(self::$config, $config);
    }

    /**
     * Retrieve a config value by key.
     *
     * @param string $key
     * @param mixed $default Returned when the key is not set.
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$config[$key] ?? $default;
    }
}
