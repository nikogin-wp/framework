<?php

namespace Nikogin\Framework\Abstracts;


use Nikogin\Framework\Contracts\Handleable;

abstract class Job implements Handleable
{
    public function __construct()
    {
        add_action("tc_".$this->getActionHook()."_job", [$this, 'handle'], 10, $this->getNumOfArgs());
    }

    /**
     * Get the action hook name.
     */
    abstract protected function getActionHook(): string;

    /**
     * Get the args number.
     *
     */
    abstract protected function getNumOfArgs(): int;

    /**
     * Handle the job logic.
     */
    abstract public function handle(...$args): mixed;
}