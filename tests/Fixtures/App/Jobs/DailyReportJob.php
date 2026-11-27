<?php

namespace Fixture\App\Jobs;

use Nikogin\Framework\Abstracts\Job;
use Nikogin\Framework\Attributes\AsCron;

#[AsCron(recurrence: 'daily')]
class DailyReportJob extends Job
{
    protected function getActionHook(): string
    {
        return 'daily_report';
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
