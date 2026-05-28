<?php

namespace Nikogin\Framework\Abstracts;

abstract class Provider
{
    abstract public function priority(): int;

    abstract public function register(): void;
}
