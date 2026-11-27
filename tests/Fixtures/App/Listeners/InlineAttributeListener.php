<?php

namespace Fixture\App\Listeners;

use Nikogin\Framework\Abstracts\Listener;
use Nikogin\Framework\Attributes\AsListener;

#[AsListener(name: 'init', type: 'action')] final class InlineAttributeListener extends Listener
{
    public function handle(mixed ...$args): mixed
    {
        return null;
    }
}
