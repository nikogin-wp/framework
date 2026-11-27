<?php

namespace Fixture\App\Listeners\Filter;

use Nikogin\Framework\Abstracts\Listener;
use Nikogin\Framework\Attributes\AsListener;

#[AsListener(name: 'the_content', type: 'filter', priority: 20, argsCount: 2)]
final class TheContentListener extends Listener
{
    public function handle(mixed ...$args): mixed
    {
        return $args[0] ?? '';
    }
}
