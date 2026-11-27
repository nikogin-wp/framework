<?php

namespace Fixture\App\Providers;

use Nikogin\Framework\Abstracts\Provider;

class LateProvider extends Provider
{
    public function priority(): int
    {
        return 20;
    }

    public function register(): void
    {
        ProviderLog::$calls[] = static::class;
    }
}
