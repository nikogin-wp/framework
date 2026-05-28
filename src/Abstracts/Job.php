<?php

namespace Nikogin\Framework\Abstracts;


use Nikogin\Framework\Contracts\Handleable;
use Nikogin\Framework\Support\Config;

abstract class Job implements Handleable
{
    public function __construct()
    {
        add_action(Config::get('short' ?? "ng")."_".$this->getActionHook()."_job", [$this, 'handle'], 10, $this->getNumOfArgs());
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