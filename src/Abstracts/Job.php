<?php

namespace Nikogin\Framework\Abstracts;


use Nikogin\Framework\Contracts\Handleable;
use Nikogin\Framework\Support\Config;

abstract class Job implements Handleable
{
    /**
     * Job classes whose action is already hooked. The container builds a new
     * instance on every Container::get(), so without this each resolve would
     * add another handler and the job would run several times per dispatch.
     *
     * @var array<class-string, true>
     */
    private static array $registered = [];

    public function __construct()
    {
        if (isset(self::$registered[static::class])) {
            return;
        }

        self::$registered[static::class] = true;

        add_action($this->hookName(), [$this, 'handle'], 10, $this->getNumOfArgs());
    }

    /**
     * Full action name: `{prefix}_{getActionHook()}_job`, e.g. `ng_send_email_job`.
     * Use it when dispatching: as_enqueue_async_action($job->hookName(), [...]).
     */
    public function hookName(): string
    {
        return Config::get('prefix', 'ng') . '_' . $this->getActionHook() . '_job';
    }

    /**
     * @internal Test helper — forget which job classes are hooked.
     */
    public static function flushRegistered(): void
    {
        self::$registered = [];
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
