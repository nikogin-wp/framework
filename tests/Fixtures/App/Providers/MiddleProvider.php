<?php

namespace Fixture\App\Providers;

use Nikogin\Framework\Abstracts\Provider;

class MiddleProvider extends Provider
{
    public function priority(): int
    {
        return 10;
    }

    public function register(): void
    {
        ProviderLog::$calls[] = static::class;
    }
}
