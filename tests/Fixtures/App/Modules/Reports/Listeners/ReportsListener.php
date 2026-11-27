<?php

namespace Fixture\App\Modules\Reports\Listeners;

use Nikogin\Framework\Abstracts\Listener;
use Nikogin\Framework\Attributes\AsListener;

#[AsListener(name: 'reports_hook', type: 'action')]
class ReportsListener extends Listener
{
    public function handle(mixed ...$args): mixed
    {
        return null;
    }
}
