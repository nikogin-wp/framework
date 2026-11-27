<?php

namespace Fixture\App\Modules\Crm\Providers;

use Nikogin\Framework\Abstracts\ServiceProvider;
use Nikogin\Framework\Attributes\AsModule;

#[AsModule(name: 'crm')]
class CrmServiceProvider extends ServiceProvider
{
    public static int $booted = 0;

    public function priority(): int
    {
        return 10;
    }

    public function boot(): void
    {
        self::$booted++;
    }
}
