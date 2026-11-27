<?php

namespace Fixture\App\Listeners;

use Nikogin\Framework\Attributes\AsListener;

#[AsListener(name: 'not_a_listener', type: 'action')]
class NotAListener
{
}
