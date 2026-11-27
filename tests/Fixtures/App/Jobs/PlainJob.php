<?php

namespace Fixture\App\Jobs;

use Nikogin\Framework\Abstracts\Job;
use Nikogin\Framework\Attributes\AsCron;


class PlainJob extends Job
{
    protected function getActionHook(): string
    {
        return 'plain';
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
