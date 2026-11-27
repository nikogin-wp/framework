<?php

namespace Fixture\App\Providers;

use Nikogin\Framework\Abstracts\Provider;

class FirstProvider extends Provider
{
    public function priority(): int
    {
        return 1;
    }

    public function register(): void
    {
        ProviderLog::$calls[] = static::class;
    }
}
