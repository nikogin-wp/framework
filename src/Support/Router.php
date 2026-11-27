<?php

namespace Nikogin\Framework\Support;

use Exception;
use InvalidArgumentException;
use Nikogin\Framework\Contracts\Middleware;
use WP_REST_Server;

/**
 * Thin wrapper around register_rest_route() that prefixes routes with the
 * configured REST namespace (Config 'namespace').
 *
 * The `$args` array mirrors the one WordPress accepts in register_rest_route():
 *
 * @phpstan-type RouteArgSchema array{
 *     default?: mixed,
 *     required?: bool,
 *     type?: string|list<string>,
 *     description?: string,
 *     enum?: list<mixed>,
 *     format?: string,
 *     items?: array<string, mixed>,
 *     properties?: array<string, mixed>,
 *     validate_callback?: callable,
 *     sanitize_callback?: callable,
 * }
 *
 * @phpstan-type RouteArgs array{
 *     methods?: string|list<string>,
 *     callback: callable,
 *     permission_callback?: callable,
 *     args?: array<string, RouteArgSchema>,
 *     show_in_index?: bool,
 *     allow_batch?: array{v1?: bool},
 *     schema?: callable,
 * }
 *
 * @phpstan-type RouteDefinition RouteArgs|array<int|'schema', RouteArgs|callable>
 */
class Router
{
    private static string $namespace = '';

    /**
     * Register a REST route.
     *
     * Accepts a single endpoint (`['methods' => ..., 'callback' => ...]`) or a
     * list of endpoints sharing the same path (e.g. GET + POST).
     * `permission_callback` defaults to `__return_true` when omitted.
     *
     * @param RouteDefinition $args
     * @throws InvalidArgumentException When an endpoint has no valid callback.
     */
    public static function add(string $route, array $args = []): void
    {
        if (self::$namespace === '') {
            self::$namespace = Config::get('namespace', '');
        }

        register_rest_route(self::$namespace, $route, self::normalize($route, $args));
    }

    /**
     * Register multiple routes under a common prefix.
     */
    public static function group(string $prefix, callable $routesRegistrar): void
    {
        if (self::$namespace === '') {
            self::$namespace = Config::get('namespace', '');
        }

        $orig = self::$namespace;
        self::$namespace = rtrim(self::$namespace, '/') . '/' . ltrim($prefix, '/');

        try {
            $routesRegistrar();
        } finally {
            self::$namespace = $orig;
        }
    }

    /**
     * Register a route with a middleware/permission callback.
     *
     * @param class-string<Middleware> $middlewareClass
     * @param RouteArgs $args
     * @throws Exception
     */
    public static function middleware(string $middlewareClass, string $route, array $args = []): void
    {
        $middleware = Container::get($middlewareClass);
        if (! $middleware instanceof Middleware) {
            throw new InvalidArgumentException("$middlewareClass must implement Middleware");
        }
        $permission = [ $middleware, 'verify' ];

        if (self::isMultiEndpoint($args)) {
            foreach ($args as $key => $endpoint) {
                if (is_int($key) && is_array($endpoint)) {
                    $args[$key]['permission_callback'] = $permission;
                }
            }
        } else {
            $args['permission_callback'] = $permission;
        }

        self::add($route, $args);
    }

    /**
     * Register a full set of RESTful routes for a resource.
     *
     * The controller must expose index, show, store, update and destroy.
     * Optional storePermission / updatePermission / destroyPermission methods
     * are used as permission callbacks when present. Without them, write
     * endpoints (store/update/destroy) are restricted to users with the
     * `manage_options` capability. They are never public by default.
     *
     * @param string $baseRoute e.g. '/items'
     * @param class-string $controllerClass Container-bound controller class
     * @throws Exception
     */
    public static function resource(string $baseRoute, string $controllerClass): void
    {
        $controller = Container::get($controllerClass);
        $idArgs     = ['id' => ['validate_callback' => fn($param) => is_numeric($param)]];

        $permission = static function (string $method) use ($controller): array {
            return ['permission_callback' => method_exists($controller, $method)
                ? [$controller, $method]
                : [self::class, 'canManage']];
        };

        self::add($baseRoute, [
            [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => [$controller, 'index'],
            ],
            [
                'methods'  => WP_REST_Server::CREATABLE,
                'callback' => [$controller, 'store'],
            ] + $permission('storePermission'),
        ]);

        self::add($baseRoute . '/(?P<id>\d+)', [
            [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => [$controller, 'show'],
                'args'     => $idArgs,
            ],
            [
                'methods'  => WP_REST_Server::EDITABLE,
                'callback' => [$controller, 'update'],
                'args'     => $idArgs,
            ] + $permission('updatePermission'),
            [
                'methods'  => WP_REST_Server::DELETABLE,
                'callback' => [$controller, 'destroy'],
                'args'     => $idArgs,
            ] + $permission('destroyPermission'),
        ]);
    }

    /**
     * Change the REST namespace (e.g. 'myplugin/v2').
     */
    public static function setNamespace(string $namespace): void
    {
        self::$namespace = $namespace;
    }

    public static function getNamespace(): string
    {
        return self::$namespace;
    }

    /**
     * Default permission for resource write endpoints without an explicit
     * *Permission() method on the controller.
     */
    public static function canManage(): bool
    {
        return current_user_can('manage_options');
    }

    /**
     * WordPress' multi-endpoint form: numeric keys hold endpoints, string
     * keys (e.g. `schema`) are route-level options.
     *
     * @param array<int|string, mixed> $args
     */
    private static function isMultiEndpoint(array $args): bool
    {
        foreach (array_keys($args) as $key) {
            if (is_int($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate the route definition and fill in defaults.
     *
     * @param array<int|string, mixed> $args
     * @return array<int|string, mixed>
     */
    private static function normalize(string $route, array $args): array
    {
        if (self::isMultiEndpoint($args)) {
            foreach ($args as $key => $value) {
                if (is_int($key)) {
                    $args[$key] = self::normalizeEndpoint($route, $value);
                }
            }

            return $args;
        }

        $schema = $args['schema'] ?? null;
        unset($args['schema']);

        $normalized = self::normalizeEndpoint($route, $args);

        if ($schema !== null) {
            $normalized['schema'] = $schema;
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    private static function normalizeEndpoint(string $route, mixed $endpoint): array
    {
        if (!is_array($endpoint)) {
            throw new InvalidArgumentException("Route \"$route\": each endpoint must be an array.");
        }

        if (!isset($endpoint['callback']) || !is_callable($endpoint['callback'])) {
            throw new InvalidArgumentException("Route \"$route\": \"callback\" must be callable.");
        }

        if (isset($endpoint['permission_callback']) && !is_callable($endpoint['permission_callback'])) {
            throw new InvalidArgumentException("Route \"$route\": \"permission_callback\" must be callable.");
        }

        if (isset($endpoint['args']) && !is_array($endpoint['args'])) {
            throw new InvalidArgumentException("Route \"$route\": \"args\" must be an array.");
        }

        $endpoint['permission_callback'] ??= '__return_true';

        return $endpoint;
    }
}
