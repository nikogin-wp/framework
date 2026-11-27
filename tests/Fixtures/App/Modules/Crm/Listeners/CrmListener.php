<?php

namespace Fixture\App\Modules\Crm\Listeners;

use Nikogin\Framework\Abstracts\Listener;
use Nikogin\Framework\Attributes\AsListener;

#[AsListener(name: 'crm_hook', type: 'action')]
class CrmListener extends Listener
{
    public function handle(mixed ...$args): mixed
    {
        return null;
    }
}
