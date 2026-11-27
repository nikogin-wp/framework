<?php

namespace Nikogin\Framework\Abstracts;

use Nikogin\Framework\Attributes\AsCron;
use Nikogin\Framework\Support\Config;
use Nikogin\Framework\Support\Discovery;
use ReflectionClass;
use RuntimeException;

/**
 * Hooks and schedules every Job carrying #[AsCron].
 *
 * - Recurring jobs are scheduled when missing and rescheduled when the
 *   attribute's recurrence changes.
 * - Single jobs are scheduled once; an option remembers it so they don't
 *   come back after running.
 * - clear() unschedules everything (call it on plugin deactivation).
 */
abstract class CronManager
{
    /** @var list<class-string<Job>> */
    protected array $jobs = [];

    public function registerJob(string $jobClass): void
    {
        if (!is_subclass_of($jobClass, Job::class) || in_array($jobClass, $this->jobs, true)) {
            return;
        }

        $this->jobs[] = $jobClass;
    }

    /**
     * @param string[] $excludeDirs
     */
    public function discover(string $directory, string $namespace, array $excludeDirs = []): void
    {
        $classes = Discovery::classesWithAttribute($directory, $namespace, AsCron::class, $excludeDirs, Job::class);

        foreach ($classes as $class) {
            $this->registerJob($class);
        }
    }

    public function register(): void
    {
        foreach ($this->jobs as $jobClass) {
            $cron = self::cronFor($jobClass);

            if ($cron === null) {
                continue;
            }

            $job  = new $jobClass(); // hooks handle() once per class
            $hook = $job->hookName();

            $cron->single ? $this->scheduleSingle($hook, $cron) : $this->scheduleRecurring($hook, $cron);
        }
    }

    /**
     * Unschedule every discovered cron job and forget single-run markers.
     */
    public function clear(): void
    {
        foreach ($this->jobs as $jobClass) {
            if (self::cronFor($jobClass) === null) {
                continue;
            }

            $hook = (new $jobClass())->hookName();

            wp_clear_scheduled_hook($hook);
            delete_option(self::singleOption($hook));
        }
    }

    private function scheduleRecurring(string $hook, AsCron $cron): void
    {
        $schedules = wp_get_schedules();

        if (!isset($schedules[$cron->recurrence])) {
            throw new RuntimeException("Unknown cron recurrence \"{$cron->recurrence}\" for {$hook}.");
        }

        $current = wp_get_schedule($hook);

        if ($current === $cron->recurrence) {
            return;
        }

        if ($current !== false) {
            wp_clear_scheduled_hook($hook);
        }

        wp_schedule_event(self::timestamp($cron->when, $hook), $cron->recurrence, $hook);
    }

    private function scheduleSingle(string $hook, AsCron $cron): void
    {
        $option = self::singleOption($hook);

        if (get_option($option) || wp_next_scheduled($hook)) {
            return;
        }

        if (wp_schedule_single_event(self::timestamp($cron->when, $hook), $hook) !== false) {
            update_option($option, 1, false);
        }
    }

    private static function cronFor(string $jobClass): ?AsCron
    {
        $attributes = (new ReflectionClass($jobClass))->getAttributes(AsCron::class);

        return $attributes === [] ? null : $attributes[0]->newInstance();
    }

    private static function timestamp(string $when, string $hook): int
    {
        $time = strtotime($when);

        if ($time === false) {
            throw new RuntimeException("Invalid cron time \"{$when}\" for {$hook}.");
        }

        return max($time, time());
    }

    private static function singleOption(string $hook): string
    {
        return Config::get('prefix', 'ng') . '_cron_scheduled_' . $hook;
    }
}
