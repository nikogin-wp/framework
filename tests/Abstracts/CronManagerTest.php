<?php

namespace Nikogin\Framework\Tests\Abstracts;

use Fixture\App\Jobs\DailyReportJob;
use Fixture\App\Jobs\HourlySyncJob;
use Fixture\App\Jobs\PlainJob;
use Fixture\App\Jobs\WelcomeJob;
use Nikogin\Framework\Abstracts\CronManager;
use Nikogin\Framework\Abstracts\Job;
use Nikogin\Framework\Attributes\AsCron;
use Nikogin\Framework\Support\Config;
use Nikogin\Framework\Tests\TestCase;
use RuntimeException;
use WpSpy;

class TestCronManager extends CronManager
{
    /** @return list<class-string> */
    public function jobs(): array
    {
        return $this->jobs;
    }
}

#[AsCron(recurrence: 'every_full_moon')]
class UnknownRecurrenceJob extends Job
{
    protected function getActionHook(): string { return 'moon'; }
    protected function getNumOfArgs(): int { return 0; }
    public function handle(...$args): mixed { return null; }
}

class CronManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Job::flushRegistered();
        Config::set(['prefix' => 'ng']);
    }

    private function manager(): TestCronManager
    {
        $manager = new TestCronManager();
        $manager->discover(self::FIXTURES, self::FIXTURE_NS);

        return $manager;
    }

    public function test_discovers_only_jobs_with_as_cron(): void
    {
        $this->assertSame(
            [DailyReportJob::class, HourlySyncJob::class, WelcomeJob::class],
            $this->manager()->jobs()
        );
    }

    public function test_schedules_recurring_and_single_jobs_and_hooks_them(): void
    {
        $this->manager()->register();

        $this->assertSame('daily', WpSpy::$cron['ng_daily_report_job']['recurrence']);
        $this->assertSame('hourly', WpSpy::$cron['ng_hourly_sync_job']['recurrence']);
        $this->assertGreaterThan(time() + 3000, WpSpy::$cron['ng_hourly_sync_job']['timestamp']);
        $this->assertFalse(WpSpy::$cron['ng_welcome_job']['recurrence']);
        $this->assertArrayNotHasKey('ng_plain_job', WpSpy::$cron);

        $this->assertCount(1, WpSpy::$actions['ng_daily_report_job']);
    }

    public function test_existing_schedule_is_left_alone(): void
    {
        WpSpy::$cron['ng_daily_report_job'] = ['timestamp' => 123, 'recurrence' => 'daily'];

        $this->manager()->register();

        $this->assertSame(123, WpSpy::$cron['ng_daily_report_job']['timestamp']);
    }

    public function test_changed_recurrence_is_rescheduled(): void
    {
        WpSpy::$cron['ng_daily_report_job'] = ['timestamp' => 123, 'recurrence' => 'hourly'];

        $this->manager()->register();

        $this->assertSame('daily', WpSpy::$cron['ng_daily_report_job']['recurrence']);
        $this->assertNotSame(123, WpSpy::$cron['ng_daily_report_job']['timestamp']);
    }

    public function test_single_job_is_not_rescheduled_after_it_ran(): void
    {
        $this->manager()->register();
        unset(WpSpy::$cron['ng_welcome_job']); // WP-Cron ran it

        $this->manager()->register();

        $this->assertArrayNotHasKey('ng_welcome_job', WpSpy::$cron);
    }

    public function test_clear_unschedules_everything(): void
    {
        $this->manager()->register();
        $this->manager()->clear();

        $this->assertSame([], WpSpy::$cron);
        $this->assertSame([], WpSpy::$options);
    }

    public function test_unknown_recurrence_throws(): void
    {
        $manager = new TestCronManager();
        $manager->registerJob(UnknownRecurrenceJob::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('every_full_moon');

        $manager->register();
    }

    public function test_register_job_ignores_non_jobs(): void
    {
        $manager = new TestCronManager();
        $manager->registerJob(\stdClass::class);

        $this->assertSame([], $manager->jobs());
    }
}
