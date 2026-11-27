<?php

namespace Nikogin\Framework\Tests\Abstracts;

use Fixture\App\Jobs\PlainJob;
use Nikogin\Framework\Abstracts\Job;
use Nikogin\Framework\Support\Config;
use Nikogin\Framework\Tests\TestCase;
use WpSpy;

class JobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Job::flushRegistered();
        Config::set(['prefix' => 'ng']);
    }

    public function test_hook_name_uses_configured_prefix(): void
    {
        Config::set(['prefix' => 'acme']);

        $this->assertSame('acme_plain_job', (new PlainJob())->hookName());
    }

    public function test_constructor_hooks_handle_with_arg_count(): void
    {
        $job = new PlainJob();

        $this->assertSame([$job, 'handle'], WpSpy::$actions['ng_plain_job'][0]['callback']);
        $this->assertSame(1, WpSpy::$actions['ng_plain_job'][0]['args']);
    }

    public function test_resolving_twice_hooks_only_once(): void
    {
        new PlainJob();
        new PlainJob();

        $this->assertCount(1, WpSpy::$actions['ng_plain_job']);
    }
}
