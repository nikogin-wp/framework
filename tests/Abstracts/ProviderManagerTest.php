<?php

namespace Nikogin\Framework\Tests\Abstracts;

use Fixture\App\Providers\FirstProvider;
use Fixture\App\Providers\LateProvider;
use Fixture\App\Providers\MiddleProvider;
use Fixture\App\Providers\ProviderLog;
use Nikogin\Framework\Abstracts\ProviderManager;
use Nikogin\Framework\Abstracts\Provider;
use Nikogin\Framework\Support\Config;
use Nikogin\Framework\Tests\TestCase;
use RuntimeException;
use WpSpy;

class TestProviderManager extends ProviderManager
{
    public function __construct(array $providers = [])
    {
        $this->providers = $providers;
    }
}

class ExplodingProvider extends Provider
{
    public function priority(): int { return 5; }

    public function register(): void
    {
        throw new RuntimeException('migration failed');
    }
}

class ProviderManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        ProviderLog::$calls = [];
        Config::set(['slug' => 'nikogin', 'version' => '1.2.0']);
    }

    private function discovered(): TestProviderManager
    {
        $manager = new TestProviderManager();
        $manager->discover(self::FIXTURES . '/Providers', self::FIXTURE_NS . '\\Providers');

        return $manager;
    }

    public function test_discovered_providers_run_in_priority_order(): void
    {
        $this->discovered()->register();

        $this->assertSame([FirstProvider::class, MiddleProvider::class, LateProvider::class], ProviderLog::$calls);
    }

    public function test_explicit_priority_overrides_provider_priority(): void
    {
        (new TestProviderManager([LateProvider::class => 0, FirstProvider::class]))->register();

        $this->assertSame([LateProvider::class, FirstProvider::class], ProviderLog::$calls);
    }

    public function test_discover_does_not_duplicate_listed_providers(): void
    {
        $manager = new TestProviderManager([FirstProvider::class => 99]);
        $manager->discover(self::FIXTURES . '/Providers', self::FIXTURE_NS . '\\Providers');
        $manager->register();

        $this->assertSame(1, count(array_keys(ProviderLog::$calls, FirstProvider::class, true)));
        $this->assertSame(FirstProvider::class, end(ProviderLog::$calls));
    }

    public function test_version_is_saved_on_fresh_install(): void
    {
        $this->discovered()->register();

        $this->assertSame('1.2.0', WpSpy::$options['nikogin_version']);
    }

    public function test_version_is_saved_after_upgrade(): void
    {
        WpSpy::$options['nikogin_version'] = '1.1.0';

        $this->discovered()->register();

        $this->assertSame('1.2.0', WpSpy::$options['nikogin_version']);
    }

    public function test_version_is_not_rewritten_when_current(): void
    {
        WpSpy::$options['nikogin_version'] = '1.2.0';

        $this->discovered()->register();

        $this->assertSame(0, WpSpy::$optionWrites);
    }

    public function test_version_is_not_saved_when_a_provider_throws(): void
    {
        try {
            (new TestProviderManager([ExplodingProvider::class]))->register();
        } catch (RuntimeException) {
        }

        $this->assertArrayNotHasKey('nikogin_version', WpSpy::$options);
    }
}
