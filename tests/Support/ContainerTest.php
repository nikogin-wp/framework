<?php

namespace Nikogin\Framework\Tests\Support;

use Exception;
use Nikogin\Framework\Support\Container;
use Nikogin\Framework\Tests\TestCase;
use stdClass;

class ContainerTest extends TestCase
{
    public function test_resolves_bound_service(): void
    {
        Container::bind('container_test.service', fn() => 'resolved');

        $this->assertSame('resolved', Container::get('container_test.service'));
    }

    public function test_rebinding_replaces_resolver(): void
    {
        Container::bind('container_test.rebind', fn() => 'first');
        Container::bind('container_test.rebind', fn() => 'second');

        $this->assertSame('second', Container::get('container_test.rebind'));
    }

    public function test_resolver_is_invoked_on_every_get(): void
    {
        Container::bind('container_test.factory', fn() => new stdClass());

        $this->assertNotSame(Container::get('container_test.factory'), Container::get('container_test.factory'));
    }

    public function test_throws_for_unknown_service(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Service not found: container_test.unknown');

        Container::get('container_test.unknown');
    }
}
