<?php

namespace Nikogin\Framework\Support;

class Assets
{
    private static ?array $manifest = null;

    /**
     * Enqueue a Vite entry point.
     * Detects JS vs CSS from the entry extension and calls the correct WP function.
     *
     * @param string $handle  WordPress enqueue handle.
     * @param string $entry   Entry key as it appears in the manifest
     *                        (e.g. 'resources/js/app.js' or 'resources/css/app.css').
     * @param array  $deps    Optional dependencies.
     */
    public static function enqueue(string $handle, string $entry, array $deps = []): void
    {
        $chunk   = self::manifest()[$entry] ?? null;
        $baseUrl = rtrim(Config::get('build_url', ''), '/') . '/';
        $version = Config::get('version');

        if (!$chunk) {
            return;
        }

        $url = $baseUrl . $chunk['file'];

        if (str_ends_with($entry, '.css') || str_ends_with($entry, '.scss')) {
            wp_enqueue_style($handle, $url, $deps, $version);
        } else {
            wp_enqueue_script($handle, $url, $deps, $version, true);

            foreach ($chunk['css'] ?? [] as $css) {
                wp_enqueue_style($handle . '-style', $baseUrl . $css, [], $version);
            }
        }
    }

    private static function manifest(): array
    {
        if (self::$manifest !== null) {
            return self::$manifest;
        }

        $path = rtrim(Config::get('build_path', ''), '/') . '/.vite/manifest.json';

        if (!file_exists($path)) {
            return self::$manifest = [];
        }

        return self::$manifest = json_decode(file_get_contents($path), true) ?? [];
    }
}
