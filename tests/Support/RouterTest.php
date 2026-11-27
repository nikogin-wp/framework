<?php

namespace Nikogin\Framework\Tests\Support;

use InvalidArgumentException;
use Nikogin\Framework\Contracts\Middleware;
use Nikogin\Framework\Support\Container;
use Nikogin\Framework\Support\Router;
use Nikogin\Framework\Tests\TestCase;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;
use WpSpy;

class RouterTestController
{
    public function index() {}
    public function show() {}
    public function store() {}
    public function update() {}
    public function destroy() {}
}

class RouterTestGuardedController extends RouterTestController
{
    public function storePermission() { return false; }
    public function destroyPermission() { return false; }
}

class RouterTestMiddleware implements Middleware
{
    public static function verify(WP_REST_Request $request): bool|WP_Error
    {
        return true;
    }
}

class RouterTest extends TestCase
{
    private function noop(): callable
    {
        return fn() => null;
    }

    public function test_add_registers_under_namespace_with_default_permission(): void
    {
        $callback = $this->noop();

        Router::add('/examples', ['methods' => WP_REST_Server::READABLE, 'callback' => $callback]);

        $this->assertCount(1, WpSpy::$routes);
        $this->assertSame('ng/v1', WpSpy::$routes[0]['namespace']);
        $this->assertSame('/examples', WpSpy::$routes[0]['route']);
        $this->assertSame($callback, WpSpy::$routes[0]['args']['callback']);
        $this->assertSame('__return_true', WpSpy::$routes[0]['args']['permission_callback']);
    }

    public function test_add_keeps_explicit_permission_and_extra_keys(): void
    {
        $permission = fn() => false;

        Router::add('/examples', [
            'callback'            => $this->noop(),
            'permission_callback' => $permission,
            'custom_attribute'    => 'kept',
        ]);

        $this->assertSame($permission, WpSpy::$routes[0]['args']['permission_callback']);
        $this->assertSame('kept', WpSpy::$routes[0]['args']['custom_attribute']);
    }

    public function test_add_accepts_list_of_endpoints(): void
    {
        Router::add('/examples', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => $this->noop()],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => $this->noop(), 'permission_callback' => 'is_object'],
        ]);

        $args = WpSpy::$routes[0]['args'];

        $this->assertCount(2, $args);
        $this->assertSame('__return_true', $args[0]['permission_callback']);
        $this->assertSame('is_object', $args[1]['permission_callback']);
    }

    public function test_schema_stays_top_level(): void
    {
        $schema = fn() => [];

        Router::add('/examples', ['callback' => $this->noop(), 'schema' => $schema]);

        $this->assertSame($schema, WpSpy::$routes[0]['args']['schema']);
    }

    public function test_list_of_endpoints_with_route_level_schema(): void
    {
        $schema = fn() => [];

        Router::add('/examples', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => $this->noop()],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => $this->noop()],
            'schema' => $schema,
        ]);

        $args = WpSpy::$routes[0]['args'];

        $this->assertSame($schema, $args['schema']);
        $this->assertSame('__return_true', $args[0]['permission_callback']);
        $this->assertSame('__return_true', $args[1]['permission_callback']);
    }

    public function test_middleware_applies_to_every_endpoint_in_list(): void
    {
        Container::bind(RouterTestMiddleware::class, fn() => new RouterTestMiddleware());

        Router::middleware(RouterTestMiddleware::class, '/secure', [
            ['methods' => WP_REST_Server::READABLE, 'callback' => $this->noop()],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => $this->noop()],
        ]);

        $args = WpSpy::$routes[0]['args'];

        $this->assertArrayNotHasKey('permission_callback', $args);
        $this->assertSame('verify', $args[0]['permission_callback'][1]);
        $this->assertSame('verify', $args[1]['permission_callback'][1]);
    }

    public function test_can_manage_requires_manage_options(): void
    {
        $this->assertFalse(Router::canManage());

        WpSpy::$caps = ['manage_options'];

        $this->assertTrue(Router::canManage());
    }

    public function test_missing_callback_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"callback" must be callable');

        Router::add('/broken', ['methods' => WP_REST_Server::READABLE]);
    }

    public function test_non_callable_callback_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Router::add('/broken', ['callback' => 'no_such_function_exists']);
    }

    public function test_non_callable_permission_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"permission_callback" must be callable');

        Router::add('/broken', ['callback' => $this->noop(), 'permission_callback' => 'nope_not_here']);
    }

    public function test_invalid_endpoint_in_list_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Router::add('/broken', ['not-an-array']);
    }

    public function test_group_prefixes_namespace_and_restores_it(): void
    {
        Router::group('/admin', fn() => Router::add('/users', ['callback' => $this->noop()]));
        Router::add('/public', ['callback' => $this->noop()]);

        $this->assertSame('ng/v1/admin', WpSpy::$routes[0]['namespace']);
        $this->assertSame('ng/v1', WpSpy::$routes[1]['namespace']);
    }

    public function test_group_restores_namespace_when_registrar_throws(): void
    {
        try {
            Router::group('/admin', fn() => throw new \RuntimeException('boom'));
        } catch (\RuntimeException) {
        }

        $this->assertSame('ng/v1', Router::getNamespace());
    }

    public function test_middleware_sets_permission_callback(): void
    {
        Container::bind(RouterTestMiddleware::class, fn() => new RouterTestMiddleware());

        Router::middleware(RouterTestMiddleware::class, '/secure', ['callback' => $this->noop()]);

        $permission = WpSpy::$routes[0]['args']['permission_callback'];
        $this->assertInstanceOf(RouterTestMiddleware::class, $permission[0]);
        $this->assertSame('verify', $permission[1]);
    }

    public function test_middleware_rejects_non_middleware(): void
    {
        Container::bind(RouterTestController::class, fn() => new RouterTestController());

        $this->expectException(InvalidArgumentException::class);

        Router::middleware(RouterTestController::class, '/secure', ['callback' => $this->noop()]);
    }

    public function test_resource_registers_restful_endpoints(): void
    {
        Container::bind(RouterTestController::class, fn() => new RouterTestController());

        Router::resource('/items', RouterTestController::class);

        $this->assertSame(['/items', '/items/(?P<id>\d+)'], array_column(WpSpy::$routes, 'route'));

        [$collection, $single] = array_column(WpSpy::$routes, 'args');

        $this->assertSame(['index', 'store'], array_map(fn($e) => $e['callback'][1], $collection));
        $this->assertSame(['show', 'update', 'destroy'], array_map(fn($e) => $e['callback'][1], $single));

        $this->assertSame('__return_true', $collection[0]['permission_callback']);
        $this->assertSame('__return_true', $single[0]['permission_callback']);

        foreach ([$collection[1], $single[1], $single[2]] as $write) {
            $this->assertSame([Router::class, 'canManage'], $write['permission_callback']);
        }

        $this->assertTrue(($single[0]['args']['id']['validate_callback'])('42'));
        $this->assertFalse(($single[0]['args']['id']['validate_callback'])('abc'));
    }

    public function test_resource_uses_permission_methods_when_present(): void
    {
        Container::bind(RouterTestGuardedController::class, fn() => new RouterTestGuardedController());

        Router::resource('/items', RouterTestGuardedController::class);

        [$collection, $single] = array_column(WpSpy::$routes, 'args');

        $this->assertSame('storePermission', $collection[1]['permission_callback'][1]);
        $this->assertSame([Router::class, 'canManage'], $single[1]['permission_callback']);
        $this->assertSame('destroyPermission', $single[2]['permission_callback'][1]);
    }
}
