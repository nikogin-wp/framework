<?php

namespace Nikogin\Framework\Support;

use Exception;
use Nikogin\Framework\Contracts\Middleware;
use Nikogin\Framework\Support\Config;
use Nikogin\Framework\Support\Container;
use InvalidArgumentException;
use WP_REST_Server;

class Router
{
    private static string $namespace = '';

    /**
     * Register a REST route.
     */
    public static function add(string $route, array $args = []): void
    {
        if (self::$namespace === '') {
            self::$namespace = Config::get('namespace', '');
        }

        if (!isset($args['permission_callback'])) {
            $args['permission_callback'] = '__return_true';
        }

        register_rest_route(self::$namespace, $route, $args);
    }


    /**
     * Register multiple routes under a common prefix.
     */
    public static function group(string $prefix, callable $routesRegistrar): void
    {
        $orig = self::$namespace;
        self::$namespace = rtrim(self::$namespace, '/') . $prefix;
        $routesRegistrar();
        self::$namespace = $orig;
    }

    /**
     * Register a route with a middleware/permission callback.
     */
    public static function middleware(string $middlewareClass, string $route, array $args = []): void
    {
        $middleware = Container::get($middlewareClass);
        if (! $middleware instanceof Middleware) {
            throw new InvalidArgumentException("$middlewareClass must implement Middleware");
        }
        $args['permission_callback'] = [ $middleware, 'verify' ];
        self::add($route, $args);
    }

    /**
     * Register a full set of RESTful routes for a resource.
     *
     * @param string $baseRoute e.g. '/items'
     * @param string $controllerClass Fully‑qualified controller class with methods:
     *                                  index, show, store, update, destroy
     * @throws Exception
     */
    public static function resource(string $baseRoute, string $controllerClass): void
    {
        // index (READABLE)
        self::add($baseRoute, [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => [Container::get($controllerClass), 'index'],
        ]);

        // show (READABLE)
        self::add($baseRoute . '/(?P<id>\d+)', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => [Container::get($controllerClass), 'show'],
            'args'     => ['id' => ['validate_callback' => 'is_numeric']],
        ]);

        // store (CREATABLE)
        self::add($baseRoute, [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [Container::get($controllerClass), 'store'],
            'permission_callback' => [Container::get($controllerClass), 'storePermission'] ?? '__return_true',
        ]);

        // update (EDITABLE)
        self::add($baseRoute . '/(?P<id>\d+)', [
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => [Container::get($controllerClass), 'update'],
            'permission_callback' => [Container::get($controllerClass), 'updatePermission'] ?? '__return_true',
            'args'                => ['id' => ['validate_callback' => 'is_numeric']],
        ]);

        // destroy (DELETABLE)
        self::add($baseRoute . '/(?P<id>\d+)', [
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => [Container::get($controllerClass), 'destroy'],
            'permission_callback' => [Container::get($controllerClass), 'destroyPermission'] ?? '__return_true',
            'args' => [
                'id' => [
                    'validate_callback' => function( $param ) {
                        return is_numeric( $param );
                    },
                ]
            ]
        ]);
    }

    /**
     * Change the REST namespace (e.g. 'myplugin/v2').
     */
    public static function setNamespace(string $namespace): void
    {
        self::$namespace = $namespace;
    }
}