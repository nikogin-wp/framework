<?php

namespace Nikogin\Framework\Abstracts;

use Nikogin\Framework\Support\Updater;

abstract class MProvider extends Provider
{
    protected array $migrations = [];

    public function register(): void
    {

        if(Updater::needsUpdate()) {

            foreach ($this->migrations as $migrationClass) {
                if (is_subclass_of($migrationClass, Migration::class)) {
                    $migrationInstance = new $migrationClass();
                    $migrationInstance->up();
                }
            }
        }
    }

}