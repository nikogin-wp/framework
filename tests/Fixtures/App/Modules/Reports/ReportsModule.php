<?php

namespace Fixture\App\Modules\Reports;

use Nikogin\Framework\Abstracts\ServiceProvider;
use Nikogin\Framework\Attributes\AsModule;

// Lives at the module root rather than Providers/*ServiceProvider.php.
#[AsModule(name: 'reports', enabled: false)]
class ReportsModule extends ServiceProvider
{
    public function priority(): int
    {
        return 10;
    }
}
