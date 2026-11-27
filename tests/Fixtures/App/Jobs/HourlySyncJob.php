<?php

namespace Fixture\App\Jobs;

use Nikogin\Framework\Abstracts\Job;
use Nikogin\Framework\Attributes\AsCron;

#[AsCron(recurrence: 'hourly', when: '+1 hour')]
class HourlySyncJob extends Job
{
    protected function getActionHook(): string
    {
        return 'hourly_sync';
    }

    protected function getNumOfArgs(): int
    {
        return 1;
    }

    public function handle(...$args): mixed
    {
        return null;
    }
}
