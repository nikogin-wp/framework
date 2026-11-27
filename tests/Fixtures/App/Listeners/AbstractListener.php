<?php

namespace Fixture\App\Listeners;

use Nikogin\Framework\Abstracts\Listener;
use Nikogin\Framework\Attributes\AsListener;

#[AsListener(name: 'abstract_hook', type: 'action')]
abstract class AbstractListener extends Listener
{
}
