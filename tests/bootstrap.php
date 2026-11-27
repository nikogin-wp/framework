<?php

/*
 * Minimal WordPress doubles so the framework can be unit-tested without a
 * WordPress install. Every call is recorded in WpSpy for assertions.
 */

require dirname(__DIR__) . '/vendor/autoload.php';

final class WpSpy
{
    /** @var array<int, array{namespace: string, route: string, args: array}> */
    public static array $routes = [];

    /** @var array<string, list<array{callback: callable, priority: int, args: int}>> */
    public static array $actions = [];

    /** @var array<string, list<array{callback: callable, priority: int, args: int}>> */
    public static array $filters = [];

    /** @var list<string> */
    public static array $caps = [];

    public static function reset(): void
    {
        self::$routes = self::$actions = self::$filters = self::$caps = [];
    }

    public static function has(array $hooks, string $name, callable $callback): bool
    {
        foreach ($hooks[$name] ?? [] as $hook) {
            if ($hook['callback'] == $callback) {
                return true;
            }
        }

        return false;
    }
}

class WP_REST_Server
{
    public const READABLE  = 'GET';
    public const CREATABLE = 'POST';
    public const EDITABLE  = 'POST, PUT, PATCH';
    public const DELETABLE = 'DELETE';
}

class WP_REST_Response
{
    public function __construct(public mixed $data = null, public int $status = 200) {}

    public function get_data(): mixed { return $this->data; }

    public function get_status(): int { return $this->status; }
}

class WP_REST_Request {}

class WP_Error {}

function register_rest_route(string $namespace, string $route, array $args = []): bool
{
    WpSpy::$routes[] = compact('namespace', 'route', 'args');
    return true;
}

function add_action(string $name, callable $callback, int $priority = 10, int $args = 1): bool
{
    WpSpy::$actions[$name][] = compact('callback', 'priority', 'args');
    return true;
}

function add_filter(string $name, callable $callback, int $priority = 10, int $args = 1): bool
{
    WpSpy::$filters[$name][] = compact('callback', 'priority', 'args');
    return true;
}

function has_action(string $name, mixed $callback = false): bool
{
    return WpSpy::has(WpSpy::$actions, $name, $callback);
}

function has_filter(string $name, mixed $callback = false): bool
{
    return WpSpy::has(WpSpy::$filters, $name, $callback);
}

function current_user_can(string $capability): bool
{
    return in_array($capability, WpSpy::$caps, true);
}

function __return_true(): bool
{
    return true;
}

// Fixture classes live under tests/Fixtures and are mapped to their own
// namespace, exactly like a plugin's app/ directory maps to its PSR-4 root.
spl_autoload_register(static function (string $class): void {
    $prefix = 'Fixture\\App\\';

    if (str_starts_with($class, $prefix)) {
        $file = __DIR__ . '/Fixtures/App/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';

        if (is_file($file)) {
            require $file;
        }
    }
});
