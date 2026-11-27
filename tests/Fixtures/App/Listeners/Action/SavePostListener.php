<?php

namespace Fixture\App\Listeners\Action;

use Nikogin\Framework\Abstracts\Listener;
use Nikogin\Framework\Attributes\AsListener;

#[AsListener(name: 'save_post', type: 'action')]
class SavePostListener extends Listener
{
    public function handle(mixed ...$args): mixed
    {
        return null;
    }
}
