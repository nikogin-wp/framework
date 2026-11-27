<?php

namespace Fixture\App\Listeners;

use Nikogin\Framework\Abstracts\Listener;

class NoAttributeListener extends Listener
{
    public function handle(mixed ...$args): mixed
    {
        return null;
    }
}
