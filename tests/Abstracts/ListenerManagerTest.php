<?php

namespace Nikogin\Framework\Tests\Abstracts;

use Fixture\App\Listeners\Action\SavePostListener;
use Fixture\App\Listeners\Filter\TheContentListener;
use Fixture\App\Services\Billing\InvoiceListener;
use Nikogin\Framework\Abstracts\ListenerManager;
use Nikogin\Framework\Tests\TestCase;
use WpSpy;

class TestListenerManager extends ListenerManager
{
    /** @return list<class-string> */
    public function listeners(): array
    {
        return $this->listeners;
    }
}

class ListenerManagerTest extends TestCase
{
    public function test_discover_registers_nested_listeners_outside_listeners_folder(): void
    {
        $manager = new TestListenerManager();
        $manager->discover(self::FIXTURES, self::FIXTURE_NS, [self::FIXTURES . '/Modules']);

        $this->assertSame([
            SavePostListener::class,
            TheContentListener::class,
            \Fixture\App\Listeners\InlineAttributeListener::class,
            InvoiceListener::class,
        ], $manager->listeners());
    }

    public function test_register_hooks_actions_and_filters_with_attribute_config(): void
    {
        $manager = new TestListenerManager();
        $manager->discover(self::FIXTURES . '/Listeners', self::FIXTURE_NS . '\\Listeners');
        $manager->register();

        $this->assertCount(1, WpSpy::$actions['save_post']);
        $this->assertInstanceOf(SavePostListener::class, WpSpy::$actions['save_post'][0]['callback'][0]);
        $this->assertSame(10, WpSpy::$actions['save_post'][0]['priority']);

        $this->assertSame(20, WpSpy::$filters['the_content'][0]['priority']);
        $this->assertSame(2, WpSpy::$filters['the_content'][0]['args']);
    }

    public function test_listeners_are_registered_once(): void
    {
        $manager = new TestListenerManager();
        $manager->registerListener(SavePostListener::class);
        $manager->discover(self::FIXTURES . '/Listeners', self::FIXTURE_NS . '\\Listeners');

        $this->assertSame(1, count(array_keys($manager->listeners(), SavePostListener::class, true)));
    }

    public function test_register_listener_ignores_non_listeners(): void
    {
        $manager = new TestListenerManager();
        $manager->registerListener(\stdClass::class);

        $this->assertSame([], $manager->listeners());
    }
}
