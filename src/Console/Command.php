<?php

namespace Nikogin\Framework\Console;

abstract class Command
{
    abstract public function name(): string;

    abstract public function handle(array $args, array $options, string $basePath): void;
}
