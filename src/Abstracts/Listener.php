<?php

namespace Nikogin\Framework\Abstracts;


use Nikogin\Framework\Contracts\Handleable;

abstract class Listener implements Handleable
{

    abstract public function handle(mixed ...$args): mixed;
}