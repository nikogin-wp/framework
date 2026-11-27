<?php

namespace Fixture\App\Services\Billing;

use Nikogin\Framework\Abstracts\Listener;
use Nikogin\Framework\Attributes\AsListener;

#[AsListener(name: 'woocommerce_order_status_completed', type: 'action')]
class InvoiceListener extends Listener
{
    public function handle(mixed ...$args): mixed
    {
        return null;
    }
}
