<?php

namespace Fixture\App\Jobs;

use Nikogin\Framework\Abstracts\Job;
use Nikogin\Framework\Attributes\AsCron;

#[AsCron(single: true, when: '+10 minutes')]
class WelcomeJob extends Job
{
    protected function getActionHook(): string
    {
        return 'welcome';
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
