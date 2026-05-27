<?php

namespace Nikogin\Framework\Abstracts;

use Nikogin\Framework\Support\Updater;

/**
 * Migration Provider class abstract
 *
 * Handles migration classes registration
 *
 *
 *
 */
abstract class MProvider
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