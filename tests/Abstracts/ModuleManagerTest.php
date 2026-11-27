<?php

namespace Nikogin\Framework\Tests\Abstracts;

use Fixture\App\Modules\Crm\Providers\CrmServiceProvider;
use Fixture\App\Modules\Reports\ReportsModule;
use Nikogin\Framework\Abstracts\ModuleManager;
use Nikogin\Framework\Support\Config;
use Nikogin\Framework\Support\Container;
use Nikogin\Framework\Tests\TestCase;

class TestModuleManager extends ModuleManager {}

class ModuleManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set(['crm' => null, 'reports' => null, 'modules' => []]);
        CrmServiceProvider::$booted = 0;
    }

    public function test_discovers_modules_anywhere_in_module_folder(): void
    {
        $modules = ModuleManager::discover(self::FIXTURES . '/Modules', self::FIXTURE_NS . '\\Modules');

        $this->assertSame([
            ['class' => CrmServiceProvider::class, 'dir' => self::FIXTURES . '/Modules/Crm'],
            ['class' => ReportsModule::class, 'dir' => self::FIXTURES . '/Modules/Reports'],
        ], $modules);
    }

    public function test_discover_missing_dir_returns_empty(): void
    {
        $this->assertSame([], ModuleManager::discover(self::FIXTURES . '/Nope', 'X'));
    }

    public function test_is_enabled_uses_attribute_default(): void
    {
        $this->assertTrue(ModuleManager::isEnabled(CrmServiceProvider::class));
        $this->assertFalse(ModuleManager::isEnabled(ReportsModule::class));
    }

    public function test_modules_config_overrides_attribute(): void
    {
        Config::set(['modules' => ['crm' => false, 'reports' => true]]);

        $this->assertFalse(ModuleManager::isEnabled(CrmServiceProvider::class));
        $this->assertTrue(ModuleManager::isEnabled(ReportsModule::class));
    }

    public function test_modules_config_parses_boolean_strings(): void
    {
        Config::set(['modules' => ['crm' => 'false', 'reports' => ' on ']]);

        $this->assertFalse(ModuleManager::isEnabled(CrmServiceProvider::class));
        $this->assertTrue(ModuleManager::isEnabled(ReportsModule::class));
    }

    public function test_unparseable_modules_value_falls_back_to_attribute(): void
    {
        Config::set(['modules' => ['crm' => 'maybe', 'reports' => ['x']]]);

        $this->assertTrue(ModuleManager::isEnabled(CrmServiceProvider::class));
        $this->assertFalse(ModuleManager::isEnabled(ReportsModule::class));
    }

    public function test_modules_config_wins_over_legacy_flat_key(): void
    {
        Config::set(['crm' => true, 'modules' => ['crm' => false]]);

        $this->assertFalse(ModuleManager::isEnabled(CrmServiceProvider::class));
    }

    public function test_legacy_non_bool_flat_key_is_ignored(): void
    {
        // e.g. a module named 'slug' must not read the plugin slug string
        Config::set(['reports' => 'nikogin']);

        $this->assertFalse(ModuleManager::isEnabled(ReportsModule::class));
    }

    public function test_legacy_flat_bool_still_works(): void
    {
        Config::set(['crm' => false, 'reports' => true]);

        $this->assertFalse(ModuleManager::isEnabled(CrmServiceProvider::class));
        $this->assertTrue(ModuleManager::isEnabled(ReportsModule::class));
    }

    public function test_class_without_attribute_is_never_enabled(): void
    {
        $this->assertFalse(ModuleManager::isEnabled(\stdClass::class));
        $this->assertFalse(ModuleManager::isEnabled('Does\\Not\\Exist'));
    }

    public function test_register_binds_and_boots_only_enabled_modules(): void
    {
        $manager = new TestModuleManager();

        foreach (ModuleManager::discover(self::FIXTURES . '/Modules', self::FIXTURE_NS . '\\Modules') as $module) {
            $manager->registerModule($module['class']);
        }

        $manager->register();

        $this->assertSame(1, CrmServiceProvider::$booted);
        $this->assertInstanceOf(CrmServiceProvider::class, Container::get(CrmServiceProvider::class));
    }
}
