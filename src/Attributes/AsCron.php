<?php

namespace Nikogin\Framework\Attributes;

use Attribute;

/**
 * Schedule a Job with WP-Cron. Put it on a class extending Job; CronManager
 * hooks the job and schedules its hookName().
 *
 * ```php
 * #[AsCron(recurrence: 'daily')]                       // every day, starting now
 * #[AsCron(recurrence: 'hourly', when: 'tomorrow 3am')] // first run tomorrow 03:00
 * #[AsCron(single: true, when: '+10 minutes')]          // run once
 * ```
 *
 * `recurrence` is any registered schedule ('hourly', 'twicedaily', 'daily',
 * 'weekly' or one added via the cron_schedules filter). `when` is anything
 * strtotime() understands.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class AsCron
{
    public function __construct(
        public string $recurrence = 'daily',
        public string $when = 'now',
        public bool $single = false,
    ) {}
}
