<?php

namespace Nikogin\Framework\Tests\Support;

use Fixture\App\Listeners\Action\SavePostListener;
use Fixture\App\Listeners\Filter\TheContentListener;
use Fixture\App\Listeners\InlineAttributeListener;
use Fixture\App\Modules\Crm\Listeners\CrmListener;
use Fixture\App\Modules\Reports\Listeners\ReportsListener;
use Fixture\App\Services\Billing\InvoiceListener;
use Nikogin\Framework\Abstracts\Listener;
use Nikogin\Framework\Attributes\AsListener;
use Nikogin\Framework\Support\Discovery;
use Nikogin\Framework\Tests\TestCase;

class DiscoveryTest extends TestCase
{
    public function test_finds_attributed_listeners_at_any_depth(): void
    {
        $found = Discovery::classesWithAttribute(self::FIXTURES, self::FIXTURE_NS, AsListener::class, [], Listener::class);

        $this->assertSame([
            SavePostListener::class,
            TheContentListener::class,
            InlineAttributeListener::class,
            CrmListener::class,
            ReportsListener::class,
            InvoiceListener::class,
        ], $found);
    }

    public function test_skips_abstract_unattributed_and_wrong_type_classes(): void
    {
        $found = Discovery::classesWithAttribute(self::FIXTURES, self::FIXTURE_NS, AsListener::class, [], Listener::class);

        $this->assertNotContains('Fixture\\App\\Listeners\\AbstractListener', $found);
        $this->assertNotContains('Fixture\\App\\Listeners\\NoAttributeListener', $found);
        $this->assertNotContains('Fixture\\App\\Listeners\\NotAListener', $found);
    }

    public function test_without_subclass_filter_returns_any_attributed_class(): void
    {
        $found = Discovery::classesWithAttribute(self::FIXTURES . '/Listeners', self::FIXTURE_NS . '\\Listeners', AsListener::class);

        $this->assertContains('Fixture\\App\\Listeners\\NotAListener', $found);
    }

    public function test_exclude_dirs_are_not_scanned(): void
    {
        $found = Discovery::classesWithAttribute(
            self::FIXTURES,
            self::FIXTURE_NS,
            AsListener::class,
            [self::FIXTURES . '/Modules'],
            Listener::class
        );

        $this->assertNotContains(CrmListener::class, $found);
        $this->assertNotContains(ReportsListener::class, $found);
        $this->assertContains(InvoiceListener::class, $found);
    }

    public function test_never_includes_scripts_or_unrelated_classes(): void
    {
        unset($GLOBALS['fixture_routes_loaded'], $GLOBALS['fixture_helpers_loaded']);

        Discovery::classesWithAttribute(self::FIXTURES, self::FIXTURE_NS, AsListener::class);

        $this->assertArrayNotHasKey('fixture_routes_loaded', $GLOBALS, 'routes/*.php must not be included');
        $this->assertArrayNotHasKey('fixture_helpers_loaded', $GLOBALS, 'lowercase scripts must not be included');
        $this->assertFalse(class_exists('Fixture\\App\\Admin\\AdminTable', false), 'unrelated classes must not be autoloaded');
    }

    public function test_missing_directory_returns_empty(): void
    {
        $this->assertSame([], Discovery::classes(self::FIXTURES . '/Nope', self::FIXTURE_NS));
    }
}
