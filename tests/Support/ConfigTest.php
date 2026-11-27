<?php

namespace Nikogin\Framework\Tests\Support;

use Nikogin\Framework\Support\Config;
use Nikogin\Framework\Tests\TestCase;

class ConfigTest extends TestCase
{
    public function test_returns_default_for_missing_key(): void
    {
        $this->assertNull(Config::get('config_test_missing'));
        $this->assertSame('fallback', Config::get('config_test_missing', 'fallback'));
    }

    public function test_later_set_overrides_and_merges(): void
    {
        Config::set(['config_test_a' => 1, 'config_test_b' => 2]);
        Config::set(['config_test_b' => 3]);

        $this->assertSame(1, Config::get('config_test_a'));
        $this->assertSame(3, Config::get('config_test_b'));
    }

    public function test_falsy_values_are_returned_not_defaulted(): void
    {
        Config::set(['config_test_false' => false]);

        $this->assertFalse(Config::get('config_test_false', true));
    }
}
